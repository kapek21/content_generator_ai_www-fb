<?php
/**
 * Strona historii publikacji
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'aicp_history';

// Paginacja
$per_page = 50;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Filtrowanie
$where = '1=1';
$filter_category = isset($_GET['filter_category']) ? intval($_GET['filter_category']) : 0;
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

if ($filter_category > 0) {
    $where .= $wpdb->prepare(' AND category_id = %d', $filter_category);
}

if (!empty($filter_status)) {
    $where .= $wpdb->prepare(' AND status = %s', $filter_status);
}

// Pobierz dane
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
$history_items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ),
    ARRAY_A
);

$total_pages = ceil($total_items / $per_page);

// Pobierz kategorie do filtra
$categories = get_categories(array(
    'exclude' => array(1),
    'hide_empty' => false,
    'orderby' => 'name'
));
?>

<div class="wrap aicp-history">
    <h1>
        <span class="dashicons dashicons-backup"></span>
        Historia Publikacji
    </h1>
    
    <!-- Filtry -->
    <div class="aicp-filters" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ccc;">
        <form method="get" action="">
            <input type="hidden" name="page" value="ai-content-publisher-history" />
            
            <label for="filter_category">Kategoria:</label>
            <select name="filter_category" id="filter_category">
                <option value="0">Wszystkie kategorie</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($filter_category, $category->term_id); ?>>
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="filter_status" style="margin-left: 15px;">Status:</label>
            <select name="filter_status" id="filter_status">
                <option value="">Wszystkie statusy</option>
                <option value="success" <?php selected($filter_status, 'success'); ?>>Sukces</option>
                <option value="error" <?php selected($filter_status, 'error'); ?>>Błąd</option>
            </select>
            
            <input type="submit" class="button" value="Filtruj" />
            
            <?php if ($filter_category > 0 || !empty($filter_status)): ?>
                <a href="?page=ai-content-publisher-history" class="button">Wyczyść filtry</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Statystyki -->
    <div class="aicp-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <?php
        $total_generations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $successful_generations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'");
        $failed_generations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'error'");
        $success_rate = $total_generations > 0 ? round(($successful_generations / $total_generations) * 100, 1) : 0;
        ?>
        
        <div class="aicp-stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #2271b1;">
            <h3 style="margin: 0 0 10px 0;">Łącznie generacji</h3>
            <div style="font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo esc_html($total_generations); ?></div>
        </div>
        
        <div class="aicp-stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #00a32a;">
            <h3 style="margin: 0 0 10px 0;">Udane</h3>
            <div style="font-size: 32px; font-weight: bold; color: #00a32a;"><?php echo esc_html($successful_generations); ?></div>
        </div>
        
        <div class="aicp-stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #d63638;">
            <h3 style="margin: 0 0 10px 0;">Nieudane</h3>
            <div style="font-size: 32px; font-weight: bold; color: #d63638;"><?php echo esc_html($failed_generations); ?></div>
        </div>
        
        <div class="aicp-stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #8c8c8c;">
            <h3 style="margin: 0 0 10px 0;">Skuteczność</h3>
            <div style="font-size: 32px; font-weight: bold; color: #8c8c8c;"><?php echo esc_html($success_rate); ?>%</div>
        </div>
    </div>
    
    <!-- Tabela historii -->
    <?php if (empty($history_items)): ?>
        <div class="notice notice-info">
            <p>Brak historii publikacji spełniającej kryteria filtrowania.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 15%;">Data</th>
                    <th style="width: 15%;">Kategoria</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 30%;">Wiadomość</th>
                    <th style="width: 25%;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history_items as $item): 
                    $category = get_category($item['category_id']);
                    $post_url = $item['post_id'] ? get_permalink($item['post_id']) : null;
                    $edit_url = $item['post_id'] ? get_edit_post_link($item['post_id']) : null;
                    
                    $fb_post_url = null;
                    if (!empty($item['facebook_post_id'])) {
                        $fb_page_id = get_option('aicp_facebook_page_id');
                        $fb_post_url = "https://www.facebook.com/{$fb_page_id}/posts/{$item['facebook_post_id']}";
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html($item['id']); ?></td>
                        <td><?php echo esc_html(date('Y-m-d H:i', strtotime($item['created_at']))); ?></td>
                        <td>
                            <?php if ($category): ?>
                                <strong><?php echo esc_html($category->name); ?></strong>
                            <?php else: ?>
                                <em>Usunięta kategoria</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['status'] === 'success'): ?>
                                <span class="aicp-status-success" style="color: #00a32a; font-weight: bold;">✓ Sukces</span>
                            <?php else: ?>
                                <span class="aicp-status-error" style="color: #d63638; font-weight: bold;">✗ Błąd</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $message = !empty($item['message']) ? $item['message'] : '-';
                            echo esc_html(mb_substr($message, 0, 100));
                            if (mb_strlen($message) > 100) echo '...';
                            ?>
                        </td>
                        <td>
                            <?php if ($post_url): ?>
                                <a href="<?php echo esc_url($post_url); ?>" target="_blank" class="button button-small">
                                    Zobacz wpis
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($edit_url): ?>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">
                                    Edytuj
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($fb_post_url): ?>
                                <a href="<?php echo esc_url($fb_post_url); ?>" target="_blank" class="button button-small">
                                    Facebook
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Paginacja -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf('Pokazano %d z %d pozycji', count($history_items), $total_items); ?>
                    </span>
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
