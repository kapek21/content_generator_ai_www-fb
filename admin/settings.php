<?php
/**
 * Strona ustawień wtyczki
 */

if (!defined('ABSPATH')) {
    exit;
}

// Zapisz ustawienia
if (isset($_POST['aicp_save_settings']) && check_admin_referer('aicp_settings_nonce')) {
    update_option('aicp_perplexity_api_key', sanitize_text_field($_POST['aicp_perplexity_api_key']));
    update_option('aicp_openai_api_key', sanitize_text_field($_POST['aicp_openai_api_key']));
    update_option('aicp_facebook_enabled', isset($_POST['aicp_facebook_enabled']) ? '1' : '0');
    update_option('aicp_facebook_page_id', sanitize_text_field($_POST['aicp_facebook_page_id']));
    update_option('aicp_facebook_access_token', sanitize_text_field($_POST['aicp_facebook_access_token']));
    update_option('aicp_keywords', sanitize_textarea_field($_POST['aicp_keywords']));
    update_option('aicp_auto_generate_enabled', isset($_POST['aicp_auto_generate_enabled']) ? '1' : '0');
    update_option('aicp_auto_generate_time', sanitize_text_field($_POST['aicp_auto_generate_time']));
    update_option('aicp_article_length', intval($_POST['aicp_article_length']));
    update_option('aicp_province_name', sanitize_text_field($_POST['aicp_province_name']));
    update_option('aicp_content_language', sanitize_text_field($_POST['aicp_content_language']));
    
    echo '<div class="notice notice-success"><p>Ustawienia zostały zapisane!</p></div>';
}

// Pobierz aktualne ustawienia
$perplexity_key = get_option('aicp_perplexity_api_key', '');
$openai_key = get_option('aicp_openai_api_key', '');
$fb_enabled = get_option('aicp_facebook_enabled', '0');
$fb_page_id = get_option('aicp_facebook_page_id', '');
$fb_token = get_option('aicp_facebook_access_token', '');
$keywords = get_option('aicp_keywords', '');
$auto_enabled = get_option('aicp_auto_generate_enabled', '0');
$auto_time = get_option('aicp_auto_generate_time', '08:00');
$article_length = get_option('aicp_article_length', '1600');
$province_name = get_option('aicp_province_name', AI_Content_Publisher::get_province_from_domain());
$content_language = get_option('aicp_content_language', 'pl');
?>

<div class="wrap aicp-settings">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        Ustawienia AI Content Publisher
    </h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('aicp_settings_nonce'); ?>
        
        <!-- API Keys Section -->
        <div class="aicp-section">
            <h2>Klucze API</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="aicp_perplexity_api_key">Klucz API Perplexity</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="aicp_perplexity_api_key"
                            name="aicp_perplexity_api_key" 
                            value="<?php echo esc_attr($perplexity_key); ?>"
                            class="regular-text"
                            placeholder="pplx-..."
                        />
                        <p class="description">
                            Uzyskaj klucz API na: <a href="https://www.perplexity.ai/settings/api" target="_blank">perplexity.ai/settings/api</a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="aicp_openai_api_key">Klucz API OpenAI</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="aicp_openai_api_key"
                            name="aicp_openai_api_key" 
                            value="<?php echo esc_attr($openai_key); ?>"
                            class="regular-text"
                            placeholder="sk-..."
                        />
                        <p class="description">
                            Uzyskaj klucz API na: <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Facebook Section -->
        <div class="aicp-section">
            <h2>Konfiguracja Facebook (Opcjonalna)</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Publikacja na Facebook</th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                name="aicp_facebook_enabled" 
                                id="aicp_facebook_enabled"
                                value="1"
                                <?php checked($fb_enabled, '1'); ?>
                            />
                            Włącz automatyczną publikację na Facebook
                        </label>
                        <p class="description">
                            Jeśli włączone, wtyczka będzie automatycznie publikować wpisy na Twojej stronie Facebook.
                            Jeśli wyłączone, artykuły będą publikowane tylko w WordPress.
                        </p>
                    </td>
                </tr>
            </table>
            
            <div id="facebook-config-section" style="<?php echo $fb_enabled === '1' ? '' : 'display:none;'; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="aicp_facebook_page_id">ID Strony Facebook</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="aicp_facebook_page_id"
                                name="aicp_facebook_page_id" 
                                value="<?php echo esc_attr($fb_page_id); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                Znajdź ID swojej strony w ustawieniach strony Facebook lub użyj 
                                <a href="https://findmyfbid.com/" target="_blank">findmyfbid.com</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="aicp_facebook_access_token">Token Dostępu Facebook</label>
                        </th>
                        <td>
                            <textarea 
                                id="aicp_facebook_access_token"
                                name="aicp_facebook_access_token" 
                                rows="3"
                                class="large-text"
                            ><?php echo esc_textarea($fb_token); ?></textarea>
                            <p class="description">
                                Wygeneruj długoterminowy token dostępu używając 
                                <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a>.
                                Potrzebne uprawnienia: pages_manage_posts, pages_read_engagement, pages_show_list
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#aicp_facebook_enabled').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#facebook-config-section').slideDown();
                    } else {
                        $('#facebook-config-section').slideUp();
                    }
                });
            });
            </script>
        </div>
        
        <!-- Content Settings -->
        <div class="aicp-section">
            <h2>Ustawienia Treści</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="aicp_content_language">Język Treści</label>
                    </th>
                    <td>
                        <select 
                            id="aicp_content_language"
                            name="aicp_content_language" 
                            class="regular-text"
                        >
                            <?php 
                            $languages = AI_Content_Publisher::get_available_languages();
                            foreach ($languages as $code => $name): 
                            ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($content_language, $code); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            Wybierz język, w którym będą generowane artykuły
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="aicp_province_name">Nazwa Województwa/Regionu</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="aicp_province_name"
                            name="aicp_province_name" 
                            value="<?php echo esc_attr($province_name); ?>"
                            class="regular-text"
                        />
                        <p class="description">
                            Nazwa województwa/regionu, którego dotyczy portal (wykryta automatycznie lub ustaw ręcznie).<br>
                            Dla języka niemieckiego użyj np. "Bayern", "Nordrhein-Westfalen", dla angielskiego "California", "Texas" itp.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="aicp_article_length">Długość Artykułu (słowa)</label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            id="aicp_article_length"
                            name="aicp_article_length" 
                            value="<?php echo esc_attr($article_length); ?>"
                            class="small-text"
                            min="800"
                            max="3000"
                            step="100"
                        />
                        <p class="description">
                            Docelowa długość generowanych artykułów (domyślnie: 1600 słów dla lepszej wartości w Google AdSense)<br>
                            <strong>Rekomendacja:</strong> 1500-1800 słów dla artykułów premium wysokiej jakości
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="aicp_keywords">Słowa Kluczowe</label>
                    </th>
                    <td>
                        <textarea 
                            id="aicp_keywords"
                            name="aicp_keywords" 
                            rows="5"
                            class="large-text"
                            placeholder="słowo1, słowo2, słowo3"
                        ><?php echo esc_textarea($keywords); ?></textarea>
                        <p class="description">
                            Oddziel słowa kluczowe przecinkami. Będą one uwzględniane podczas wyszukiwania newsów i pisania artykułów.
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Category Frequency Settings -->
        <div class="aicp-section">
            <h2>Częstotliwość Generowania dla Kategorii</h2>
            <p class="description">Ustaw częstotliwość generowania artykułów dla każdej kategorii osobno. Te ustawienia są również dostępne w panelu głównym.</p>
            
            <?php
            $categories = get_categories(array(
                'exclude' => array(1),
                'hide_empty' => false,
                'orderby' => 'name'
            ));
            
            if (!empty($categories)):
            ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Kategoria</th>
                            <th style="width: 25%;">Częstotliwość</th>
                            <th style="width: 25%;">Ostatnio generowano</th>
                            <th style="width: 20%;">Następna publikacja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($categories as $category):
                            $frequency = AI_Content_Publisher::get_category_frequency($category->term_id);
                            $frequency_options = AI_Content_Publisher::get_frequency_options();
                            $last_generated = AI_Content_Publisher::get_last_generated($category->term_id);
                            $next_date = AI_Content_Publisher::get_next_generation_date($category->term_id);
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($category->name); ?></strong>
                                    <br><small class="description">Liczba wpisów: <?php echo esc_html($category->count); ?></small>
                                </td>
                                <td>
                                    <select 
                                        class="aicp-frequency-select" 
                                        data-category-id="<?php echo esc_attr($category->term_id); ?>"
                                        style="width: 100%;">
                                        <?php foreach ($frequency_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($frequency, $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="frequency-save-status" style="margin-left: 5px; color: green; display: none;">✓</span>
                                </td>
                                <td>
                                    <?php if (!empty($last_generated)): ?>
                                        <?php echo esc_html(date('Y-m-d H:i', strtotime($last_generated))); ?>
                                    <?php else: ?>
                                        <em>Nigdy</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($next_date); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="description" style="margin-top: 15px;">
                    <strong>Wyjaśnienie opcji:</strong><br>
                    • <strong>Codziennie</strong> - nowy artykuł każdego dnia<br>
                    • <strong>Co 2/3 dni</strong> - artykuł co kilka dni<br>
                    • <strong>Raz w tygodniu</strong> - jeden artykuł na tydzień<br>
                    • <strong>Raz na 2 tygodnie</strong> - co dwa tygodnie<br>
                    • <strong>Raz w miesiącu</strong> - jeden artykuł miesięcznie<br>
                    • <strong>Wyłączone</strong> - brak automatycznego generowania dla tej kategorii
                </p>
            <?php else: ?>
                <p class="notice notice-warning">Brak kategorii do skonfigurowania.</p>
            <?php endif; ?>
        </div>
        
        <!-- Automation Settings -->
        <div class="aicp-section">
            <h2>Automatyzacja</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Automatyczne Generowanie</th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                name="aicp_auto_generate_enabled" 
                                value="1"
                                <?php checked($auto_enabled, '1'); ?>
                            />
                            Włącz automatyczne generowanie artykułów
                        </label>
                        <p class="description">
                            Gdy włączone, wtyczka będzie automatycznie generować artykuły dla wszystkich kategorii.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="aicp_auto_generate_time">Godzina Automatycznego Generowania</label>
                    </th>
                    <td>
                        <input 
                            type="time" 
                            id="aicp_auto_generate_time"
                            name="aicp_auto_generate_time" 
                            value="<?php echo esc_attr($auto_time); ?>"
                        />
                        <p class="description">
                            Godzina, o której ma się odbywać automatyczne generowanie (domyślnie: 08:00)
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input 
                type="submit" 
                name="aicp_save_settings" 
                class="button button-primary button-large" 
                value="Zapisz Ustawienia"
            />
        </p>
    </form>
    
    <!-- Instructions Section -->
    <div class="aicp-section" style="margin-top: 30px; background: #f0f8ff; padding: 20px; border-left: 4px solid #2271b1;">
        <h2>📚 Instrukcja Konfiguracji</h2>
        
        <h3>1. Perplexity API</h3>
        <ol>
            <li>Zarejestruj się na <a href="https://www.perplexity.ai/" target="_blank">perplexity.ai</a></li>
            <li>Przejdź do ustawień API i wygeneruj klucz</li>
            <li>Skopiuj klucz i wklej powyżej</li>
        </ol>
        
        <h3>2. OpenAI API</h3>
        <ol>
            <li>Załóż konto na <a href="https://platform.openai.com/" target="_blank">platform.openai.com</a></li>
            <li>Dodaj metodę płatności (API jest płatne)</li>
            <li>Wygeneruj klucz API w sekcji API Keys</li>
            <li>Skopiuj klucz i wklej powyżej</li>
        </ol>
        
        <h3>3. Facebook Graph API</h3>
        <ol>
            <li>Przejdź do <a href="https://developers.facebook.com/" target="_blank">developers.facebook.com</a></li>
            <li>Utwórz nową aplikację (typ: Business)</li>
            <li>Dodaj produkt "Facebook Login"</li>
            <li>Użyj <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a>:
                <ul>
                    <li>Wybierz swoją aplikację</li>
                    <li>Wygeneruj token z uprawnieniami: pages_manage_posts, pages_read_engagement</li>
                    <li>Zamień na długoterminowy token używając debugera tokenów</li>
                </ul>
            </li>
            <li>Skopiuj ID strony Facebook i token</li>
        </ol>
        
        <p><strong>Uwaga:</strong> Zachowaj klucze API w bezpiecznym miejscu i nigdy nie udostępniaj ich publicznie!</p>
    </div>
</div>
