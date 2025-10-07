<?php
/**
 * Plugin Name: AI Content Publisher
 * Plugin URI: https://twojadomena.pl
 * Description: Automatyczne generowanie i publikowanie artykułów wykorzystując Perplexity, OpenAI i Facebook Graph API z konfigurowalną częstotliwością dla każdej kategorii oraz wsparciem dla wielu języków (PL, DE, EN, UK)
 * Version: 1.2.0
 * Author: Twoja Nazwa
 * Author URI: https://twojadomena.pl
 * License: GPL2
 * Text Domain: ai-content-publisher
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('AICP_VERSION', '1.2.0');
define('AICP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICP_PLUGIN_URL', plugin_dir_url(__FILE__));

class AI_Content_Publisher {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Załaduj komponenty
        $this->load_dependencies();
        
        // Akcje i filtry
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX akcje
        add_action('wp_ajax_aicp_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_aicp_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_aicp_save_category_frequency', array($this, 'ajax_save_category_frequency'));
        
        // Cron dla automatycznego generowania
        add_action('aicp_auto_generate_event', array($this, 'auto_generate_content'));
        
        // Aktywacja/deaktywacja
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    private function load_dependencies() {
        require_once AICP_PLUGIN_DIR . 'includes/class-perplexity-api.php';
        require_once AICP_PLUGIN_DIR . 'includes/class-openai-api.php';
        require_once AICP_PLUGIN_DIR . 'includes/class-facebook-api.php';
        require_once AICP_PLUGIN_DIR . 'includes/class-content-generator.php';
    }
    
    public function activate() {
        // Ustaw harmonogram cron (domyślnie: codziennie o 8:00)
        if (!wp_next_scheduled('aicp_auto_generate_event')) {
            wp_schedule_event(strtotime('08:00:00'), 'daily', 'aicp_auto_generate_event');
        }
        
        // Utwórz folder dla obrazów
        $upload_dir = wp_upload_dir();
        $aicp_dir = $upload_dir['basedir'] . '/ai-content-publisher';
        if (!file_exists($aicp_dir)) {
            wp_mkdir_p($aicp_dir);
        }
    }
    
    public function deactivate() {
        // Usuń harmonogram cron
        $timestamp = wp_next_scheduled('aicp_auto_generate_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aicp_auto_generate_event');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'AI Content Publisher',
            'AI Publisher',
            'manage_options',
            'ai-content-publisher',
            array($this, 'render_admin_page'),
            'dashicons-edit-large',
            30
        );
        
        add_submenu_page(
            'ai-content-publisher',
            'Ustawienia',
            'Ustawienia',
            'manage_options',
            'ai-content-publisher-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'ai-content-publisher',
            'Historia publikacji',
            'Historia',
            'manage_options',
            'ai-content-publisher-history',
            array($this, 'render_history_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ai-content-publisher') === false) {
            return;
        }
        
        wp_enqueue_style(
            'aicp-admin-styles',
            AICP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            AICP_VERSION
        );
        
        wp_enqueue_script(
            'aicp-admin-script',
            AICP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            AICP_VERSION,
            true
        );
        
        wp_localize_script('aicp-admin-script', 'aicpAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicp_nonce')
        ));
    }
    
    public function register_settings() {
        // Perplexity API
        register_setting('aicp_settings', 'aicp_perplexity_api_key');
        
        // OpenAI API
        register_setting('aicp_settings', 'aicp_openai_api_key');
        
        // Facebook API
        register_setting('aicp_settings', 'aicp_facebook_enabled');
        register_setting('aicp_settings', 'aicp_facebook_page_id');
        register_setting('aicp_settings', 'aicp_facebook_access_token');
        
        // Słowa kluczowe
        register_setting('aicp_settings', 'aicp_keywords');
        
        // Ustawienia generowania
        register_setting('aicp_settings', 'aicp_auto_generate_enabled');
        register_setting('aicp_settings', 'aicp_auto_generate_time');
        register_setting('aicp_settings', 'aicp_article_length');
        register_setting('aicp_settings', 'aicp_province_name');
        register_setting('aicp_settings', 'aicp_content_language');
    }
    
    public function render_admin_page() {
        require_once AICP_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    public function render_settings_page() {
        require_once AICP_PLUGIN_DIR . 'admin/settings.php';
    }
    
    public function render_history_page() {
        require_once AICP_PLUGIN_DIR . 'admin/history.php';
    }
    
    public function ajax_generate_content() {
        check_ajax_referer('aicp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        
        try {
            $generator = new AICP_Content_Generator();
            $result = $generator->generate_and_publish($category_id);
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('aicp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
        $results = array();
        
        try {
            switch ($service) {
                case 'perplexity':
                    $api = new AICP_Perplexity_API();
                    $results['perplexity'] = $api->test_connection();
                    break;
                    
                case 'openai':
                    $api = new AICP_OpenAI_API();
                    $results['openai'] = $api->test_connection();
                    break;
                    
                case 'facebook':
                    $api = new AICP_Facebook_API();
                    $results['facebook'] = $api->test_connection();
                    break;
                    
                case 'all':
                    $perplexity = new AICP_Perplexity_API();
                    $openai = new AICP_OpenAI_API();
                    
                    $results['perplexity'] = $perplexity->test_connection();
                    $results['openai'] = $openai->test_connection();
                    
                    // Testuj Facebook tylko jeśli włączony
                    if (get_option('aicp_facebook_enabled', '0') === '1') {
                        $facebook = new AICP_Facebook_API();
                        $results['facebook'] = $facebook->test_connection();
                    } else {
                        $results['facebook'] = 'disabled';
                    }
                    break;
            }
            
            wp_send_json_success($results);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function auto_generate_content() {
        if (!get_option('aicp_auto_generate_enabled')) {
            return;
        }
        
        $generator = new AICP_Content_Generator();
        
        // Pobierz wszystkie kategorie z wyjątkiem "Bez kategorii"
        $categories = get_categories(array(
            'exclude' => array(1), // ID kategorii "Bez kategorii" to zazwyczaj 1
            'hide_empty' => false
        ));
        
        foreach ($categories as $category) {
            // Sprawdź, czy kategoria powinna być generowana dzisiaj
            if (!$this->should_generate_for_category($category->term_id)) {
                continue;
            }
            
            try {
                $generator->generate_and_publish($category->term_id);
                
                // Zapisz log i aktualizuj ostatnią datę generowania
                $this->log_generation($category->term_id, 'success');
                $this->update_last_generated($category->term_id);
                
                // Odczekaj między generowaniem, aby nie przekroczyć limitów API
                sleep(10);
            } catch (Exception $e) {
                // Zapisz błąd
                $this->log_generation($category->term_id, 'error', $e->getMessage());
            }
        }
    }
    
    private function log_generation($category_id, $status, $message = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aicp_history';
        
        $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'status' => $status,
                'message' => $message,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    // Pomocnicza funkcja do pobierania nazwy województwa z domeny
    public static function get_province_from_domain() {
        $saved_province = get_option('aicp_province_name');
        if (!empty($saved_province)) {
            return $saved_province;
        }
        
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        $domain = str_replace('www.', '', $domain);
        
        // Lista województw
        $provinces = array(
            'zachodniopomorskie' => 'zachodniopomorskie',
            'pomorskie' => 'pomorskie',
            'warminsko-mazurskie' => 'warmińsko-mazurskie',
            'warminskomazurskie' => 'warmińsko-mazurskie',
            'podlaskie' => 'podlaskie',
            'mazowieckie' => 'mazowieckie',
            'lubelskie' => 'lubelskie',
            'podkarpackie' => 'podkarpackie',
            'malopolskie' => 'małopolskie',
            'slaskie' => 'śląskie',
            'opolskie' => 'opolskie',
            'dolnoslaskie' => 'dolnośląskie',
            'dolnośląskie' => 'dolnośląskie',
            'lubuskie' => 'lubuskie',
            'wielkopolskie' => 'wielkopolskie',
            'kujawsko-pomorskie' => 'kujawsko-pomorskie',
            'lodzkie' => 'łódzkie',
            'swietokrzyskie' => 'świętokrzyskie'
        );
        
        foreach ($provinces as $key => $name) {
            if (strpos($domain, $key) !== false) {
                return $name;
            }
        }
        
        return 'mazowieckie'; // Domyślne
    }
    
    /**
     * AJAX: Zapisuje częstotliwość dla kategorii
     */
    public function ajax_save_category_frequency() {
        check_ajax_referer('aicp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'daily';
        
        if ($category_id <= 0) {
            wp_send_json_error('Nieprawidłowy ID kategorii');
        }
        
        // Zapisz częstotliwość dla kategorii
        $frequencies = get_option('aicp_category_frequencies', array());
        $frequencies[$category_id] = $frequency;
        update_option('aicp_category_frequencies', $frequencies);
        
        wp_send_json_success(array(
            'category_id' => $category_id,
            'frequency' => $frequency,
            'message' => 'Częstotliwość zapisana'
        ));
    }
    
    /**
     * Pobiera częstotliwość dla kategorii
     */
    public static function get_category_frequency($category_id) {
        $frequencies = get_option('aicp_category_frequencies', array());
        return isset($frequencies[$category_id]) ? $frequencies[$category_id] : 'daily';
    }
    
    /**
     * Sprawdza, czy kategoria powinna być generowana dzisiaj
     */
    private function should_generate_for_category($category_id) {
        $frequency = self::get_category_frequency($category_id);
        
        // Jeśli wyłączone, nie generuj
        if ($frequency === 'disabled') {
            return false;
        }
        
        // Pobierz datę ostatniego generowania
        $last_generated = get_option('aicp_last_generated_' . $category_id, '');
        
        if (empty($last_generated)) {
            // Nigdy nie generowano, generuj teraz
            return true;
        }
        
        $last_date = strtotime($last_generated);
        $current_date = current_time('timestamp');
        $days_diff = floor(($current_date - $last_date) / DAY_IN_SECONDS);
        
        // Sprawdź częstotliwość
        switch ($frequency) {
            case 'daily':
                return $days_diff >= 1;
            case 'every_2_days':
                return $days_diff >= 2;
            case 'every_3_days':
                return $days_diff >= 3;
            case 'weekly':
                return $days_diff >= 7;
            case 'biweekly':
                return $days_diff >= 14;
            case 'monthly':
                return $days_diff >= 30;
            default:
                return $days_diff >= 1;
        }
    }
    
    /**
     * Aktualizuje datę ostatniego generowania dla kategorii
     */
    private function update_last_generated($category_id) {
        update_option('aicp_last_generated_' . $category_id, current_time('mysql'));
    }
    
    /**
     * Pobiera datę ostatniego generowania dla kategorii
     */
    public static function get_last_generated($category_id) {
        return get_option('aicp_last_generated_' . $category_id, '');
    }
    
    /**
     * Pobiera następną datę generowania dla kategorii
     */
    public static function get_next_generation_date($category_id) {
        $frequency = self::get_category_frequency($category_id);
        $last_generated = self::get_last_generated($category_id);
        
        if ($frequency === 'disabled') {
            return 'Wyłączone';
        }
        
        if (empty($last_generated)) {
            return 'Przy najbliższym uruchomieniu';
        }
        
        $last_date = strtotime($last_generated);
        
        $days_to_add = 1;
        switch ($frequency) {
            case 'daily':
                $days_to_add = 1;
                break;
            case 'every_2_days':
                $days_to_add = 2;
                break;
            case 'every_3_days':
                $days_to_add = 3;
                break;
            case 'weekly':
                $days_to_add = 7;
                break;
            case 'biweekly':
                $days_to_add = 14;
                break;
            case 'monthly':
                $days_to_add = 30;
                break;
        }
        
        $next_date = $last_date + ($days_to_add * DAY_IN_SECONDS);
        
        if ($next_date <= current_time('timestamp')) {
            return 'Przy najbliższym uruchomieniu';
        }
        
        return date_i18n('Y-m-d H:i', $next_date);
    }
    
    /**
     * Pobiera opcje częstotliwości
     */
    public static function get_frequency_options() {
        return array(
            'daily' => 'Codziennie',
            'every_2_days' => 'Co 2 dni',
            'every_3_days' => 'Co 3 dni',
            'weekly' => 'Raz w tygodniu',
            'biweekly' => 'Raz na 2 tygodnie',
            'monthly' => 'Raz w miesiącu',
            'disabled' => 'Wyłączone'
        );
    }
    
    /**
     * Pobiera dostępne języki
     */
    public static function get_available_languages() {
        return array(
            'pl' => 'Polski',
            'de' => 'Deutsch (Niemiecki)',
            'en' => 'English (Angielski)',
            'uk' => 'Українська (Ukraiński)'
        );
    }
    
    /**
     * Pobiera pełne nazwy języków dla API
     */
    public static function get_language_full_name($lang_code) {
        $names = array(
            'pl' => 'Polish',
            'de' => 'German',
            'en' => 'English',
            'uk' => 'Ukrainian'
        );
        return isset($names[$lang_code]) ? $names[$lang_code] : 'Polish';
    }
    
    /**
     * Pobiera natywną nazwę języka
     */
    public static function get_language_native_name($lang_code) {
        $names = array(
            'pl' => 'polskim',
            'de' => 'niemieckim',
            'en' => 'angielskim',
            'uk' => 'ukraińskim'
        );
        return isset($names[$lang_code]) ? $names[$lang_code] : 'polskim';
    }
}

// Inicjalizacja wtyczki
function aicp_init() {
    return AI_Content_Publisher::get_instance();
}
add_action('plugins_loaded', 'aicp_init');

// Tworzenie tabeli przy aktywacji
register_activation_hook(__FILE__, 'aicp_create_tables');
function aicp_create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aicp_history';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        category_id bigint(20) NOT NULL,
        post_id bigint(20) DEFAULT NULL,
        facebook_post_id varchar(255) DEFAULT NULL,
        status varchar(50) NOT NULL,
        message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category_id (category_id),
        KEY post_id (post_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
