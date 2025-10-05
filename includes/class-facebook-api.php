<?php
/**
 * Klasa do komunikacji z Facebook Graph API
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICP_Facebook_API {
    
    private $access_token;
    private $page_id;
    private $api_version = 'v18.0';
    private $api_url;
    
    public function __construct() {
        $this->access_token = get_option('aicp_facebook_access_token');
        $this->page_id = get_option('aicp_facebook_page_id');
        $this->api_url = "https://graph.facebook.com/{$this->api_version}";
    }
    
    /**
     * Testuje połączenie z API
     */
    public function test_connection() {
        if (empty($this->access_token) || empty($this->page_id)) {
            throw new Exception('Brak tokenu dostępu lub ID strony Facebook');
        }
        
        $url = "{$this->api_url}/{$this->page_id}?fields=name,access_token&access_token={$this->access_token}";
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z Facebook: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['error'])) {
            throw new Exception('Błąd Facebook API: ' . $decoded['error']['message']);
        }
        
        return isset($decoded['name']);
    }
    
    /**
     * Publikuje post na stronie Facebook
     */
    public function publish_post($message, $link = null, $image_url = null) {
        if (empty($this->access_token) || empty($this->page_id)) {
            throw new Exception('Brak konfiguracji Facebook API');
        }
        
        // Jeśli mamy obraz, najpierw go przesyłamy
        if (!empty($image_url)) {
            return $this->publish_photo_post($message, $link, $image_url);
        }
        
        // Post tekstowy z linkiem
        $url = "{$this->api_url}/{$this->page_id}/feed";
        
        $params = array(
            'message' => $message,
            'access_token' => $this->access_token
        );
        
        if (!empty($link)) {
            $params['link'] = $link;
        }
        
        $args = array(
            'method' => 'POST',
            'body' => $params,
            'timeout' => 30
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd publikacji na Facebook: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['error'])) {
            throw new Exception('Błąd Facebook API: ' . $decoded['error']['message']);
        }
        
        return $decoded;
    }
    
    /**
     * Publikuje post ze zdjęciem
     */
    private function publish_photo_post($message, $link, $image_url) {
        $url = "{$this->api_url}/{$this->page_id}/photos";
        
        $params = array(
            'url' => $image_url,
            'caption' => $message,
            'access_token' => $this->access_token
        );
        
        if (!empty($link)) {
            // Facebook nie pozwala na bezpośrednie dodanie linku do posta ze zdjęciem
            // Dodajemy link do wiadomości
            $params['caption'] = $message . "\n\n" . $link;
        }
        
        $args = array(
            'method' => 'POST',
            'body' => $params,
            'timeout' => 60
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd publikacji zdjęcia na Facebook: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['error'])) {
            throw new Exception('Błąd Facebook API (zdjęcie): ' . $decoded['error']['message']);
        }
        
        return $decoded;
    }
    
    /**
     * Pobiera informacje o stronie
     */
    public function get_page_info() {
        if (empty($this->access_token) || empty($this->page_id)) {
            throw new Exception('Brak konfiguracji Facebook API');
        }
        
        $url = "{$this->api_url}/{$this->page_id}?fields=name,fan_count,picture&access_token={$this->access_token}";
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z Facebook: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['error'])) {
            throw new Exception('Błąd Facebook API: ' . $decoded['error']['message']);
        }
        
        return $decoded;
    }
}
