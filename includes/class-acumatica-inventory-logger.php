<?php
class Acumatica_Inventory_Logger {
    private static $table_name = 'acumatica_inventory_log';

    public static function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acumatica_inventory_log';
    
        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            sku VARCHAR(100) NOT NULL,
            old_qty INT(11) NOT NULL,
            new_qty INT(11) NOT NULL,
            status VARCHAR(255) NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) " . $wpdb->get_charset_collate() . ";";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }    

    public static function log_update($sku, $old_qty, $new_qty, $timestamp, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acumatica_inventory_log';
    
        $wpdb->insert(
            $table_name,
            [
                'sku' => $sku,
                'old_qty' => $old_qty,
                'new_qty' => $new_qty,
                'status' => $status,
                'timestamp' => $timestamp
            ],
            ['%s', '%d', '%d', '%s', '%s']
        );
    }
    
}
