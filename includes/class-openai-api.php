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
    public function generate_article($news_data, $category_name, $province, $keywords, $target_length = 1200, $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $system_prompt = $this->build_article_system_prompt($target_length, $language);
        $user_prompt = $this->build_article_user_prompt($news_data, $category_name, $province, $keywords, $language);
        
        $response = $this->chat($user_prompt, 'gpt-4o', $system_prompt, 4000);
        
        return $response;
    }
    
    /**
     * Generuje krótki wpis na Facebooka
     */
    public function generate_facebook_post($article_title, $article_excerpt, $province, $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $prompt = $this->build_facebook_prompt($article_title, $article_excerpt, $province, $language);
        $system_prompt = $this->build_facebook_system_prompt($language);
        
        $response = $this->chat($prompt, 'gpt-4o-mini', $system_prompt, 500);
        
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
    private function build_article_system_prompt($target_length, $language = 'pl') {
        $language_name = AI_Content_Publisher::get_language_full_name($language);
        
        $prompts = array(
            'pl' => "Jesteś doświadczonym dziennikarzem i copywriterem specjalizującym się w tworzeniu treści SEO. " .
                   "Twoje artykuły są dobrze napisane, interesujące i zoptymalizowane pod kątem wyszukiwarek. " .
                   "Piszesz w języku polskim, używając naturalnego, płynnego stylu. " .
                   "Artykuły zawierają około {$target_length} słów.",
            'de' => "Du bist ein erfahrener Journalist und Texter, der sich auf die Erstellung von SEO-Inhalten spezialisiert hat. " .
                   "Deine Artikel sind gut geschrieben, interessant und für Suchmaschinen optimiert. " .
                   "Du schreibst auf Deutsch mit einem natürlichen, fließenden Stil. " .
                   "Die Artikel enthalten etwa {$target_length} Wörter.",
            'en' => "You are an experienced journalist and copywriter specializing in creating SEO content. " .
                   "Your articles are well-written, interesting and optimized for search engines. " .
                   "You write in English, using a natural, flowing style. " .
                   "Articles contain approximately {$target_length} words.",
            'uk' => "Ти досвідчений журналіст і копірайтер, який спеціалізується на створенні SEO-контенту. " .
                   "Твої статті добре написані, цікаві та оптимізовані для пошукових систем. " .
                   "Ти пишеш українською мовою, використовуючи природний, плавний стиль. " .
                   "Статті містять приблизно {$target_length} слів."
        );
        
        return isset($prompts[$language]) ? $prompts[$language] : $prompts['pl'];
    }
    
    /**
     * Buduje user prompt dla generowania artykułu
     */
    private function build_article_user_prompt($news_data, $category_name, $province, $keywords, $language = 'pl') {
        $templates = array(
            'pl' => array(
                'intro' => "Napisz artykuł publicystyczny na temat: {$category_name} w województwie {$province}.\n\n",
                'base' => "Bazuj na poniższych aktualnych informacjach:\n{$news_data}\n\n",
                'requirements' => "WYMAGANIA:\n",
                'req1' => "1. Artykuł powinien mieć około 1200 słów\n",
                'req2' => "2. Nazwa województwa '{$province}' musi pojawić się co najmniej 3 razy w tekście\n",
                'req3' => "3. Naturalnie wpleć następujące słowa kluczowe: %s\n",
                'req4' => "4. Użyj struktury: tytuł (H1), wprowadzenie, 3-4 sekcje z podtytułami (H2), podsumowanie\n",
                'req5' => "5. Pisz w sposób angażujący, używając konkretnych faktów i danych\n",
                'req6' => "6. Zachowaj neutralny, profesjonalny ton dziennikarza\n",
                'req7' => "7. Dodaj elementy storytellingu, aby tekst był ciekawy\n",
                'req8' => "8. Zoptymalizuj pod SEO - naturalne użycie słów kluczowych\n\n",
                'format' => "Zwróć artykuł w formacie HTML z odpowiednimi tagami (h1, h2, p, strong)."
            ),
            'de' => array(
                'intro' => "Schreibe einen redaktionellen Artikel zum Thema: {$category_name} im Bundesland {$province}.\n\n",
                'base' => "Basiere auf folgenden aktuellen Informationen:\n{$news_data}\n\n",
                'requirements' => "ANFORDERUNGEN:\n",
                'req1' => "1. Der Artikel sollte etwa 1200 Wörter umfassen\n",
                'req2' => "2. Der Name des Bundeslandes '{$province}' muss mindestens 3-mal im Text erscheinen\n",
                'req3' => "3. Füge natürlich folgende Schlüsselwörter ein: %s\n",
                'req4' => "4. Verwende folgende Struktur: Titel (H1), Einleitung, 3-4 Abschnitte mit Untertiteln (H2), Zusammenfassung\n",
                'req5' => "5. Schreibe ansprechend und verwende konkrete Fakten und Daten\n",
                'req6' => "6. Behalte einen neutralen, professionellen journalistischen Ton bei\n",
                'req7' => "7. Füge Storytelling-Elemente hinzu, um den Text interessant zu gestalten\n",
                'req8' => "8. Optimiere für SEO - natürliche Verwendung von Schlüsselwörtern\n\n",
                'format' => "Gib den Artikel im HTML-Format mit entsprechenden Tags zurück (h1, h2, p, strong)."
            ),
            'en' => array(
                'intro' => "Write an editorial article on: {$category_name} in the state/region of {$province}.\n\n",
                'base' => "Base it on the following current information:\n{$news_data}\n\n",
                'requirements' => "REQUIREMENTS:\n",
                'req1' => "1. The article should be approximately 1200 words\n",
                'req2' => "2. The name of the state/region '{$province}' must appear at least 3 times in the text\n",
                'req3' => "3. Naturally incorporate the following keywords: %s\n",
                'req4' => "4. Use the structure: title (H1), introduction, 3-4 sections with subtitles (H2), summary\n",
                'req5' => "5. Write engagingly, using specific facts and data\n",
                'req6' => "6. Maintain a neutral, professional journalistic tone\n",
                'req7' => "7. Add storytelling elements to make the text interesting\n",
                'req8' => "8. Optimize for SEO - natural use of keywords\n\n",
                'format' => "Return the article in HTML format with appropriate tags (h1, h2, p, strong)."
            ),
            'uk' => array(
                'intro' => "Напиши публіцистичну статтю на тему: {$category_name} в регіоні {$province}.\n\n",
                'base' => "Базуйся на наступній актуальній інформації:\n{$news_data}\n\n",
                'requirements' => "ВИМОГИ:\n",
                'req1' => "1. Стаття повинна містити близько 1200 слів\n",
                'req2' => "2. Назва регіону '{$province}' повинна з'явитися принаймні 3 рази в тексті\n",
                'req3' => "3. Природно вплети наступні ключові слова: %s\n",
                'req4' => "4. Використовуй структуру: заголовок (H1), вступ, 3-4 розділи з підзаголовками (H2), висновок\n",
                'req5' => "5. Пиши захоплююче, використовуючи конкретні факти та дані\n",
                'req6' => "6. Дотримуйся нейтрального, професійного журналістського тону\n",
                'req7' => "7. Додай елементи сторітелінгу, щоб текст був цікавим\n",
                'req8' => "8. Оптимізуй під SEO - природне використання ключових слів\n\n",
                'format' => "Поверни статтю у форматі HTML з відповідними тегами (h1, h2, p, strong)."
            )
        );
        
        $template = isset($templates[$language]) ? $templates[$language] : $templates['pl'];
        
        $prompt = $template['intro'];
        $prompt .= $template['base'];
        $prompt .= $template['requirements'];
        $prompt .= $template['req1'];
        $prompt .= $template['req2'];
        
        if (!empty($keywords)) {
            $keywords_str = implode(', ', $keywords);
            $prompt .= sprintf($template['req3'], $keywords_str);
        }
        
        $prompt .= $template['req4'];
        $prompt .= $template['req5'];
        $prompt .= $template['req6'];
        $prompt .= $template['req7'];
        $prompt .= $template['req8'];
        $prompt .= $template['format'];
        
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
    
    /**
     * Buduje prompt dla wpisu na Facebooku
     */
    private function build_facebook_prompt($article_title, $article_excerpt, $province, $language = 'pl') {
        $templates = array(
            'pl' => "Na podstawie poniższego artykułu napisz krótki, angażujący wpis na Facebooka (maksymalnie 250 znaków).\n\n" .
                   "Tytuł artykułu: {$article_title}\n\n" .
                   "Fragment artykułu: {$article_excerpt}\n\n" .
                   "Wpis powinien być zachęcający do przeczytania całości, zawierać emocje i hashtagi. " .
                   "Uwzględnij województwo: {$province}",
            'de' => "Schreibe basierend auf dem folgenden Artikel einen kurzen, ansprechenden Facebook-Post (maximal 250 Zeichen).\n\n" .
                   "Artikeltitel: {$article_title}\n\n" .
                   "Artikelauszug: {$article_excerpt}\n\n" .
                   "Der Beitrag sollte zum Lesen des gesamten Artikels anregen, Emotionen enthalten und Hashtags verwenden. " .
                   "Beziehe das Bundesland ein: {$province}",
            'en' => "Based on the article below, write a short, engaging Facebook post (maximum 250 characters).\n\n" .
                   "Article title: {$article_title}\n\n" .
                   "Article excerpt: {$article_excerpt}\n\n" .
                   "The post should encourage reading the full article, contain emotions and use hashtags. " .
                   "Include the state/region: {$province}",
            'uk' => "На основі наступної статті напиши короткий, захоплюючий пост для Facebook (максимум 250 символів).\n\n" .
                   "Заголовок статті: {$article_title}\n\n" .
                   "Фрагмент статті: {$article_excerpt}\n\n" .
                   "Пост повинен спонукати до прочитання повної статті, містити емоції та використовувати хештеги. " .
                   "Включи регіон: {$province}"
        );
        
        return isset($templates[$language]) ? $templates[$language] : $templates['pl'];
    }
    
    /**
     * Buduje system prompt dla wpisu na Facebooku
     */
    private function build_facebook_system_prompt($language = 'pl') {
        $prompts = array(
            'pl' => 'Jesteś ekspertem od mediów społecznościowych. Tworzysz krótkie, angażujące wpisy na Facebooka.',
            'de' => 'Du bist ein Social-Media-Experte. Du erstellst kurze, ansprechende Facebook-Posts.',
            'en' => 'You are a social media expert. You create short, engaging Facebook posts.',
            'uk' => 'Ти експерт з соціальних мереж. Ти створюєш короткі, захоплюючі пости для Facebook.'
        );
        
        return isset($prompts[$language]) ? $prompts[$language] : $prompts['pl'];
    }
}
