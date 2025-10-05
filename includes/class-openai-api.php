<?php
/**
 * Klasa do komunikacji z OpenAI API (ChatGPT i DALL-E)
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICP_OpenAI_API {
    
    private $api_key;
    private $chat_api_url = 'https://api.openai.com/v1/chat/completions';
    private $image_api_url = 'https://api.openai.com/v1/images/generations';
    
    public function __construct() {
        $this->api_key = get_option('aicp_openai_api_key');
    }
    
    /**
     * Testuje połączenie z API
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $response = $this->chat('Odpowiedz jednym słowem: OK', 'gpt-4o-mini');
        return !empty($response);
    }
    
    /**
     * Generuje artykuł na podstawie zebranych informacji
     */
    public function generate_article($news_data, $category_name, $province, $keywords, $target_length = 1200) {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $system_prompt = $this->build_article_system_prompt($target_length);
        $user_prompt = $this->build_article_user_prompt($news_data, $category_name, $province, $keywords);
        
        $response = $this->chat($user_prompt, 'gpt-4o', $system_prompt, 4000);
        
        return $response;
    }
    
    /**
     * Generuje krótki wpis na Facebooka
     */
    public function generate_facebook_post($article_title, $article_excerpt, $province) {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $prompt = "Na podstawie poniższego artykułu napisz krótki, angażujący wpis na Facebooka (maksymalnie 250 znaków).\n\n";
        $prompt .= "Tytuł artykułu: {$article_title}\n\n";
        $prompt .= "Fragment artykułu: {$article_excerpt}\n\n";
        $prompt .= "Wpis powinien być zachęcający do przeczytania całości, zawierać emocje i hashtagi. ";
        $prompt .= "Uwzględnij województwo: {$province}";
        
        $response = $this->chat($prompt, 'gpt-4o-mini', 'Jesteś ekspertem od mediów społecznościowych. Tworzysz krótkie, angażujące wpisy na Facebooka.', 500);
        
        return $response;
    }
    
    /**
     * Generuje obraz dla artykułu
     */
    public function generate_image($article_title, $category_name) {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        // Przygotuj prompt dla DALL-E
        $image_prompt = $this->build_image_prompt($article_title, $category_name);
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'dall-e-3',
                'prompt' => $image_prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard'
            )),
            'timeout' => 120
        );
        
        $response = wp_remote_request($this->image_api_url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z OpenAI (obrazy): ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['data'][0]['url'])) {
            return $decoded['data'][0]['url'];
        }
        
        throw new Exception('Błąd generowania obrazu: ' . $body);
    }
    
    /**
     * Uniwersalna funkcja chat
     */
    private function chat($message, $model = 'gpt-4o-mini', $system_prompt = null, $max_tokens = 2000) {
        $messages = array();
        
        if ($system_prompt) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_prompt
            );
        }
        
        $messages[] = array(
            'role' => 'user',
            'content' => $message
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $max_tokens,
                'temperature' => 0.7
            )),
            'timeout' => 60
        );
        
        $response = wp_remote_request($this->chat_api_url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z OpenAI: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            throw new Exception('Błąd API OpenAI (kod: ' . $status_code . '): ' . $body);
        }
        
        $decoded = json_decode($body, true);
        
        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }
        
        throw new Exception('Brak odpowiedzi z OpenAI');
    }
    
    /**
     * Buduje system prompt dla generowania artykułu
     */
    private function build_article_system_prompt($target_length) {
        return "Jesteś doświadczonym dziennikarzem i copywriterem specjalizującym się w tworzeniu treści SEO. " .
               "Twoje artykuły są dobrze napisane, interesujące i zoptymalizowane pod kątem wyszukiwarek. " .
               "Piszesz w języku polskim, używając naturalnego, płynnego stylu. " .
               "Artykuły zawierają około {$target_length} słów.";
    }
    
    /**
     * Buduje user prompt dla generowania artykułu
     */
    private function build_article_user_prompt($news_data, $category_name, $province, $keywords) {
        $prompt = "Napisz artykuł publicystyczny na temat: {$category_name} w województwie {$province}.\n\n";
        $prompt .= "Bazuj na poniższych aktualnych informacjach:\n{$news_data}\n\n";
        $prompt .= "WYMAGANIA:\n";
        $prompt .= "1. Artykuł powinien mieć około 1200 słów\n";
        $prompt .= "2. Nazwa województwa '{$province}' musi pojawić się co najmniej 3 razy w tekście\n";
        
        if (!empty($keywords)) {
            $keywords_str = implode(', ', $keywords);
            $prompt .= "3. Naturalnie wpleć następujące słowa kluczowe: {$keywords_str}\n";
        }
        
        $prompt .= "4. Użyj struktury: tytuł (H1), wprowadzenie, 3-4 sekcje z podtytułami (H2), podsumowanie\n";
        $prompt .= "5. Pisz w sposób angażujący, używając konkretnych faktów i danych\n";
        $prompt .= "6. Zachowaj neutralny, profesjonalny ton dziennikarza\n";
        $prompt .= "7. Dodaj elementy storytellingu, aby tekst był ciekawy\n";
        $prompt .= "8. Zoptymalizuj pod SEO - naturalne użycie słów kluczowych\n\n";
        $prompt .= "Zwróć artykuł w formacie HTML z odpowiednimi tagami (h1, h2, p, strong).";
        
        return $prompt;
    }
    
    /**
     * Buduje prompt dla generowania obrazu
     */
    private function build_image_prompt($article_title, $category_name) {
        // DALL-E preferuje opisy po angielsku
        $prompt = "A professional, high-quality editorial image representing: {$article_title}. ";
        $prompt .= "Category: {$category_name}. ";
        $prompt .= "Style: modern, clean, photorealistic, suitable for news article. ";
        $prompt .= "No text or watermarks in the image.";
        
        return $prompt;
    }
}
