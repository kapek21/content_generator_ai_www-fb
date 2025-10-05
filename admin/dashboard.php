<?php
/**
 * Panel główny wtyczki
 */

if (!defined('ABSPATH')) {
    exit;
}

$province = AI_Content_Publisher::get_province_from_domain();
$categories = get_categories(array(
    'exclude' => array(1),
    'hide_empty' => false,
    'orderby' => 'name'
));
?>

<div class="wrap aicp-dashboard">
    <h1>
        <span class="dashicons dashicons-edit-large"></span>
        AI Content Publisher - Panel Główny
    </h1>
    
    <div class="aicp-info-box">
        <h2>Informacje o systemie</h2>
        <p><strong>Wykryte województwo:</strong> <?php echo esc_html($province); ?></p>
        <p><strong>Liczba kategorii:</strong> <?php echo count($categories); ?></p>
        <p class="description">
            Wtyczka automatycznie generuje artykuły dla każdej kategorii w Twoim WordPressie, 
            wykorzystując najnowsze informacje z Perplexity i publikując je na Facebook.
        </p>
    </div>
    
    <div class="aicp-section">
        <h2>Test połączeń API</h2>
        <div class="aicp-api-tests">
            <button type="button" class="button button-secondary" id="test-all-apis">
                Testuj wszystkie połączenia
            </button>
            <div id="api-test-results" style="margin-top: 15px;"></div>
        </div>
    </div>
    
    <div class="aicp-section">
        <h2>Generowanie treści</h2>
        <p>Wybierz kategorię, dla której chcesz wygenerować artykuł:</p>
        
        <?php if (empty($categories)): ?>
            <div class="notice notice-warning">
                <p>Nie znaleziono żadnych kategorii. Utwórz kategorie w WordPress przed użyciem wtyczki.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 25%;">Kategoria</th>
                        <th style="width: 10%;">Liczba wpisów</th>
                        <th style="width: 20%;">Częstotliwość</th>
                        <th style="width: 20%;">Następne generowanie</th>
                        <th style="width: 25%;">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($categories as $category): 
                        $frequency = AI_Content_Publisher::get_category_frequency($category->term_id);
                        $frequency_options = AI_Content_Publisher::get_frequency_options();
                        $next_date = AI_Content_Publisher::get_next_generation_date($category->term_id);
                        $last_generated = AI_Content_Publisher::get_last_generated($category->term_id);
                    ?>
                        <tr data-category-id="<?php echo esc_attr($category->term_id); ?>">
                            <td>
                                <strong><?php echo esc_html($category->name); ?></strong>
                                <?php if (!empty($category->description)): ?>
                                    <br><span class="description"><?php echo esc_html($category->description); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($last_generated)): ?>
                                    <br><small class="description">Ostatnio: <?php echo esc_html(date('Y-m-d H:i', strtotime($last_generated))); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($category->count); ?></td>
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
                                <span class="next-generation-date">
                                    <?php echo esc_html($next_date); ?>
                                </span>
                            </td>
                            <td>
                                <button 
                                    type="button" 
                                    class="button button-primary generate-content-btn"
                                    data-category-id="<?php echo esc_attr($category->term_id); ?>"
                                    data-category-name="<?php echo esc_attr($category->name); ?>">
                                    Generuj artykuł
                                </button>
                                <span class="spinner" style="float: none; margin: 0 10px;"></span>
                                <span class="generation-status"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px;">
                <button type="button" class="button button-secondary" id="generate-all-categories">
                    Generuj dla wszystkich kategorii
                </button>
                <p class="description">
                    Uwaga: Generowanie dla wszystkich kategorii może zająć kilka minut.
                    Upewnij się, że masz wystarczające limity API.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="aicp-section">
        <h2>Ostatnie generacje</h2>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'aicp_history';
        $recent_generations = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10",
            ARRAY_A
        );
        
        if (empty($recent_generations)):
        ?>
            <p>Brak historii generowania.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Kategoria</th>
                        <th>Status</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_generations as $generation): 
                        $category = get_category($generation['category_id']);
                        $post_url = $generation['post_id'] ? get_permalink($generation['post_id']) : null;
                    ?>
                        <tr>
                            <td><?php echo esc_html($generation['created_at']); ?></td>
                            <td><?php echo $category ? esc_html($category->name) : 'Nieznana'; ?></td>
                            <td>
                                <?php if ($generation['status'] === 'success'): ?>
                                    <span class="aicp-status-success">✓ Sukces</span>
                                <?php else: ?>
                                    <span class="aicp-status-error">✗ Błąd</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($post_url): ?>
                                    <a href="<?php echo esc_url($post_url); ?>" target="_blank" class="button button-small">
                                        Zobacz wpis
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
