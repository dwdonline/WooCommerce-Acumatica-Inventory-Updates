<?php
/**
 * Plugin Name: Acumatica Inventory Updates
 * Plugin URI: https://dwdonline.com
 * Description: Update WooCommerce inventory based on Acumatica stock levels and log the updates.
 * Version: 1.0
 * Author: Philip N. Deatherage
 * Author URI: https://dwdonline.com
 * Text Domain: acumatica-inventory-updates
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the class files.
require_once plugin_dir_path(__FILE__) . 'includes/class-acumatica-inventory-updater.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-acumatica-inventory-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-acumatica-inventory-admin.php';

// Activation hook for creating the log table.
register_activation_hook(__FILE__, ['Acumatica_Inventory_Logger', 'create_log_table']);

register_activation_hook(__FILE__, 'acumatica_inventory_plugin_activation');

function acumatica_inventory_plugin_activation() {
    $administrator = get_role('administrator');
    if (!$administrator->has_cap('acumatica_inventory_log_view')) {
        $administrator->add_cap('acumatica_inventory_log_view');
    }
}

// Initialize the inventory updater and admin panel.
Acumatica_Inventory_Updater::init();
Acumatica_Inventory_Admin::init();
