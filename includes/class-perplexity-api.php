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
    public function search_news($category_name, $province, $keywords = array()) {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API Perplexity');
        }
        
        // Przygotuj zapytanie
        $query = $this->build_search_query($category_name, $province, $keywords);
        
        $response = $this->make_request([
            'model' => 'sonar-pro', // ResearchModels: do szczegółowych analiz i raportów
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Jesteś asystentem, który wyszukuje najnowsze informacje i newsy. Odpowiadaj po polsku.'
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
     * Buduje zapytanie wyszukiwania
     */
    private function build_search_query($category_name, $province, $keywords) {
        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        $query = "Wyszukaj najnowsze informacje, wydarzenia i newsy związane z tematyką: {$category_name}, ";
        $query .= "które dotyczą województwa {$province} lub regionu {$province}. ";
        $query .= "Skup się na wydarzeniach z ostatnich 7 dni (od {$week_ago} do {$today}). ";
        
        if (!empty($keywords)) {
            $keywords_str = implode(', ', $keywords);
            $query .= "Uwzględnij następujące słowa kluczowe: {$keywords_str}. ";
        }
        
        $query .= "Przedstaw co najmniej 5-7 najważniejszych aktualnych informacji z tego zakresu. ";
        $query .= "Dla każdej informacji podaj konkretne fakty, daty i szczegóły.";
        
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
