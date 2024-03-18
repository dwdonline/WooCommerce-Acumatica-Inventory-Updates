<?php
class Acumatica_Inventory_Admin {
    public static function init() {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Acumatica Log',
            'Acumatica Log',
            'acumatica_inventory_log_view',
            'acumatica-log',
            [self::class, 'display_log_page']
        );
    }

    public static function display_log_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acumatica_inventory_log';
    
        $search = $_POST['search'] ?? '';
        $orderby = $_GET['orderby'] ?? 'timestamp';
        $order = $_GET['order'] ?? 'desc';
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 10;
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $offset = ($current_page - 1) * $per_page;
    
        $sql = "SELECT * FROM $table_name WHERE sku LIKE %s ORDER BY $orderby $order LIMIT $per_page OFFSET $offset";
        $logs = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($search) . '%'), ARRAY_A);
    
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE sku LIKE '%" . $wpdb->esc_like($search) . "%'");
    
        $total_pages = ceil($total_logs / $per_page);
    
        echo '<div class="wrap"><h1>Acumatica Inventory Log</h1>';
        echo '<form class="paging-menu" style="float:right;" method="post"><input type="text" name="search" placeholder="Search SKU" value="' . esc_attr($search) . '"><input class="button" type="submit" value="Search"></form>';
    
        // Display per page dropdown
        $per_page_options = array(10, 25, 50, 100, 500, 1000);
        echo '<form method="get"><select name="per_page">';
        foreach ($per_page_options as $option) {
            echo '<option value="' . $option . '" ' . selected($per_page, $option, false) . '>' . $option . ' per page</option>';
        }
        echo '</select><input type="hidden" name="page" value="acumatica-log"><input type="submit" class="button" value="Apply"></form>';
    
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>SKU</th><th>Old Quantity</th><th>New Quantity</th><th>Status</th><th>Timestamp (Local Time)</th></tr></thead><tbody>';
        foreach ($logs as $log) {
            // Retrieve the current user's timezone
            $timezone = get_user_meta(get_current_user_id(), 'timezone_string', true);
            $timezone = empty($timezone) ? 'America/Los_Angeles' : $timezone;
    
            // Convert UTC timestamp to the user's timezone
            $local_timestamp = new DateTime($log['timestamp'], new DateTimeZone('UTC'));
            $local_timestamp->setTimezone(new DateTimeZone($timezone));
            $local_timestamp = $local_timestamp->format('Y-m-d H:i:s');
    
            echo '<tr><td>' . esc_html($log['sku']) . '</td><td>' . esc_html($log['old_qty']) . '</td><td>' . esc_html($log['new_qty']) . '</td><td>' . esc_html($log['status']) . '</td><td>' . esc_html($local_timestamp) . '</td></tr>';
        }
        echo '</tbody></table>';
    
        // Display pagination
        if ($total_pages > 1) {
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'total' => $total_pages,
                'current' => $current_page,
            ));
            if ($page_links) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
            }
        }
    
        echo '</div>';
    }    
    
}

