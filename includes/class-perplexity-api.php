<?php
/**
 * Klasa do komunikacji z Perplexity API
 * 
 * Dostępne modele Perplexity:
 * - sonar (SearchModels): do szybkich zapytań faktualnych i podsumowań
 * - sonar-reasoning (ReasoningModels): do zadań wymagających złożonego rozumowania i wieloetapowych analiz
 * - sonar-pro (ResearchModels): do szczegółowych analiz i raportów
 * 
 * Wtyczka używa 'sonar-pro' dla szczegółowego wyszukiwania newsów i wydarzeń.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICP_Perplexity_API {
    
    private $api_key;
    private $api_url = 'https://api.perplexity.ai/chat/completions';
    
    public function __construct() {
        $this->api_key = get_option('aicp_perplexity_api_key');
    }
    
    /**
     * Testuje połączenie z API
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API Perplexity');
        }
        
        $response = $this->make_request([
            'model' => 'sonar',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test'
                ]
            ]
        ]);
        
        return !empty($response);
    }
    
    /**
     * Wyszukuje najnowsze newsy dla danej kategorii i województwa
     */
    public function search_news($category_name, $province, $keywords = array(), $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API Perplexity');
        }
        
        // Przygotuj zapytanie
        $query = $this->build_search_query($category_name, $province, $keywords, $language);
        
        // Przygotuj system prompt w odpowiednim języku
        $system_prompt = $this->get_system_prompt($language);
        
        $response = $this->make_request([
            'model' => 'sonar-pro', // ResearchModels: do szczegółowych analiz i raportów
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ],
                [
                    'role' => 'user',
                    'content' => $query
                ]
            ],
            'temperature' => 0.2,
            'max_tokens' => 4000
        ]);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        throw new Exception('Brak odpowiedzi z Perplexity API');
    }
    
    /**
     * Pobiera system prompt w wybranym języku
     */
    private function get_system_prompt($language) {
        $prompts = array(
            'pl' => 'Jesteś asystentem, który wyszukuje najnowsze informacje i newsy. Odpowiadaj po polsku.',
            'de' => 'Du bist ein Assistent, der die neuesten Informationen und Nachrichten sucht. Antworte auf Deutsch.',
            'en' => 'You are an assistant that searches for the latest information and news. Respond in English.',
            'uk' => 'Ти помічник, який шукає найновішу інформацію та новини. Відповідай українською.'
        );
        
        return isset($prompts[$language]) ? $prompts[$language] : $prompts['pl'];
    }
    
    /**
     * Buduje zapytanie wyszukiwania
     */
    private function build_search_query($category_name, $province, $keywords, $language = 'pl') {
        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        // Szablony zapytań dla różnych języków
        $templates = array(
            'pl' => array(
                'intro' => "Wyszukaj najnowsze informacje, wydarzenia i newsy związane z tematyką: {$category_name}, które dotyczą województwa {$province} lub regionu {$province}. ",
                'timeframe' => "Skup się na wydarzeniach z ostatnich 7 dni (od {$week_ago} do {$today}). ",
                'keywords' => "Uwzględnij następujące słowa kluczowe: %s. ",
                'summary' => "Przedstaw co najmniej 5-7 najważniejszych aktualnych informacji z tego zakresu. Dla każdej informacji podaj konkretne fakty, daty i szczegóły."
            ),
            'de' => array(
                'intro' => "Suche die neuesten Informationen, Ereignisse und Nachrichten zum Thema: {$category_name}, die das Bundesland {$province} oder die Region {$province} betreffen. ",
                'timeframe' => "Konzentriere dich auf Ereignisse der letzten 7 Tage (vom {$week_ago} bis {$today}). ",
                'keywords' => "Berücksichtige folgende Schlüsselwörter: %s. ",
                'summary' => "Präsentiere mindestens 5-7 der wichtigsten aktuellen Informationen aus diesem Bereich. Gib für jede Information konkrete Fakten, Daten und Details an."
            ),
            'en' => array(
                'intro' => "Search for the latest information, events and news related to: {$category_name}, concerning the state/region of {$province}. ",
                'timeframe' => "Focus on events from the last 7 days (from {$week_ago} to {$today}). ",
                'keywords' => "Consider the following keywords: %s. ",
                'summary' => "Present at least 5-7 of the most important current information from this area. For each item, provide specific facts, dates and details."
            ),
            'uk' => array(
                'intro' => "Знайди найновішу інформацію, події та новини, пов'язані з темою: {$category_name}, що стосуються регіону {$province}. ",
                'timeframe' => "Зосередься на подіях останніх 7 днів (з {$week_ago} до {$today}). ",
                'keywords' => "Враховуй наступні ключові слова: %s. ",
                'summary' => "Представ принаймні 5-7 найважливіших актуальних інформацій з цієї сфери. Для кожної інформації надай конкретні факти, дати та деталі."
            )
        );
        
        $template = isset($templates[$language]) ? $templates[$language] : $templates['pl'];
        
        $query = $template['intro'];
        $query .= $template['timeframe'];
        
        if (!empty($keywords)) {
            $keywords_str = implode(', ', $keywords);
            $query .= sprintf($template['keywords'], $keywords_str);
        }
        
        $query .= $template['summary'];
        
        return $query;
    }
    
    /**
     * Wykonuje zapytanie do API
     */
    private function make_request($data) {
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 60
        );
        
        $response = wp_remote_request($this->api_url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z Perplexity: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            throw new Exception('Błąd API Perplexity (kod: ' . $status_code . '): ' . $body);
        }
        
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Błąd parsowania odpowiedzi z Perplexity');
        }
        
        return $decoded;
    }
}
