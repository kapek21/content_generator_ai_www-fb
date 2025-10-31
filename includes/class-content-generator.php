<?php
/**
 * Główna klasa generująca i publikująca treści
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICP_Content_Generator {
    
    private $perplexity;
    private $openai;
    private $facebook;
    private $province;
    private $keywords;
    private $language;
    
    public function __construct() {
        $this->perplexity = new AICP_Perplexity_API();
        $this->openai = new AICP_OpenAI_API();
        $this->facebook = new AICP_Facebook_API();
        $this->province = AI_Content_Publisher::get_province_from_domain();
        $this->language = get_option('aicp_content_language', 'pl');
        
        // Pobierz słowa kluczowe
        $keywords_string = get_option('aicp_keywords', '');
        $this->keywords = array_filter(array_map('trim', explode(',', $keywords_string)));
    }
    
    /**
     * Główna funkcja generująca i publikująca treść
     */
    public function generate_and_publish($category_id) {
        // Pobierz informacje o kategorii
        $category = get_category($category_id);
        
        if (!$category || is_wp_error($category)) {
            throw new Exception('Nieprawidłowa kategoria');
        }
        
        // Pomiń kategorię "Bez kategorii"
        if ($category->name === 'Bez kategorii' || $category->slug === 'uncategorized') {
            throw new Exception('Pomijanie kategorii "Bez kategorii"');
        }
        
        $result = array(
            'category' => $category->name,
            'province' => $this->province,
            'steps' => array()
        );
        
        // Krok 1: Wyszukaj aktualności przez Perplexity
        $result['steps'][] = 'Wyszukiwanie aktualności przez Perplexity...';
        $news_data = $this->perplexity->search_news(
            $category->name,
            $this->province,
            $this->keywords,
            $this->language
        );
        
        if (empty($news_data)) {
            throw new Exception('Nie znaleziono aktualności dla kategorii: ' . $category->name);
        }
        
        // Krok 2: Wygeneruj artykuł przez OpenAI
        $result['steps'][] = 'Generowanie artykułu przez OpenAI...';
        $article_length = get_option('aicp_article_length', 1600);
        $article_html = $this->openai->generate_article(
            $news_data,
            $category->name,
            $this->province,
            $this->keywords,
            $article_length,
            $this->language
        );
        
        // Wyodrębnij tytuł z artykułu
        $article_title = $this->extract_title($article_html);
        $article_excerpt = $this->create_excerpt($article_html);
        
        // Krok 3: Wygeneruj obraz
        $result['steps'][] = 'Generowanie obrazu przez DALL-E...';
        $image_url = $this->openai->generate_image($article_title, $category->name);
        
        // Pobierz i zapisz obraz w media library
        $image_id = $this->download_and_save_image($image_url, $article_title);
        
        // Krok 4: Utwórz wpis w WordPress
        $result['steps'][] = 'Tworzenie wpisu w WordPress...';
        $post_id = $this->create_wordpress_post(
            $article_title,
            $article_html,
            $category_id,
            $image_id
        );
        
        $result['post_id'] = $post_id;
        $result['post_url'] = get_permalink($post_id);
        
        // Sprawdź czy publikacja na Facebook jest włączona
        $facebook_enabled = get_option('aicp_facebook_enabled', '0') === '1';
        $facebook_post_id = null;
        
        if ($facebook_enabled) {
            // Krok 5: Wygeneruj wpis na Facebook
            $result['steps'][] = 'Generowanie wpisu na Facebook...';
            $facebook_message = $this->openai->generate_facebook_post(
                $article_title,
                $article_excerpt,
                $this->province,
                $this->language
            );
            
            // Krok 6: Opublikuj na Facebook
            $result['steps'][] = 'Publikacja na Facebook...';
            
            // Pobierz URL obrazu z WordPress
            $wp_image_url = wp_get_attachment_url($image_id);
            
            $facebook_response = $this->facebook->publish_post(
                $facebook_message,
                get_permalink($post_id),
                $wp_image_url
            );
            
            $facebook_post_id = isset($facebook_response['id']) ? $facebook_response['id'] : null;
            $result['facebook_post_id'] = $facebook_post_id;
        } else {
            $result['steps'][] = 'Publikacja na Facebook wyłączona - pomijam...';
        }
        
        $result['steps'][] = 'Ukończono! ✓';
        
        // Zapisz w historii
        $this->save_to_history($category_id, $post_id, $facebook_post_id);
        
        // Aktualizuj datę ostatniego generowania
        update_option('aicp_last_generated_' . $category_id, current_time('mysql'));
        
        return $result;
    }
    
    /**
     * Wyodrębnia tytuł z HTML
     */
    private function extract_title($html) {
        // Spróbuj znaleźć tag H1
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $matches)) {
            return strip_tags($matches[1]);
        }
        
        // Jeśli nie ma H1, użyj pierwszych 60 znaków
        $text = strip_tags($html);
        $text = substr($text, 0, 60);
        return $text . '...';
    }
    
    /**
     * Tworzy fragment (excerpt) z artykułu
     */
    private function create_excerpt($html) {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text); // Usuń wielokrotne spacje
        $text = trim($text);
        
        if (mb_strlen($text) > 300) {
            $text = mb_substr($text, 0, 300) . '...';
        }
        
        return $text;
    }
    
    /**
     * Pobiera obraz z URL i zapisuje w media library
     */
    private function download_and_save_image($image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Pobierz obraz
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            throw new Exception('Błąd pobierania obrazu: ' . $temp_file->get_error_message());
        }
        
        // Przygotuj dane pliku
        $file_array = array(
            'name' => sanitize_file_name($title) . '.png',
            'tmp_name' => $temp_file
        );
        
        // Przenieś do media library
        $image_id = media_handle_sideload($file_array, 0, $title);
        
        // Usuń tymczasowy plik
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        if (is_wp_error($image_id)) {
            throw new Exception('Błąd zapisywania obrazu: ' . $image_id->get_error_message());
        }
        
        return $image_id;
    }
    
    /**
     * Tworzy wpis w WordPress
     */
    private function create_wordpress_post($title, $content, $category_id, $featured_image_id) {
        // Usuń tag H1 z contentu (będzie użyty jako tytuł)
        $content = preg_replace('/<h1[^>]*>.*?<\/h1>/i', '', $content);
        
        $post_data = array(
            'post_title' => wp_strip_all_tags($title),
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id() ?: 1,
            'post_category' => array($category_id),
            'post_type' => 'post'
        );
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Błąd tworzenia wpisu: ' . $post_id->get_error_message());
        }
        
        // Ustaw featured image
        if ($featured_image_id) {
            set_post_thumbnail($post_id, $featured_image_id);
        }
        
        // Dodaj meta informacje
        update_post_meta($post_id, '_aicp_generated', true);
        update_post_meta($post_id, '_aicp_generation_date', current_time('mysql'));
        update_post_meta($post_id, '_aicp_province', $this->province);
        
        return $post_id;
    }
    
    /**
     * Zapisuje informacje w historii
     */
    private function save_to_history($category_id, $post_id, $facebook_post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aicp_history';
        
        $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'post_id' => $post_id,
                'facebook_post_id' => $facebook_post_id,
                'status' => 'success',
                'message' => 'Wygenerowano i opublikowano pomyślnie',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
}
