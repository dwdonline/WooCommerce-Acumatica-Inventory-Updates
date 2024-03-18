<?php
class Acumatica_Inventory_Updater {

    public static function init() {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route('wc/v3', '/acumatica-inventory-updates', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_inventory_update'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    public static function handle_inventory_update($request) {
        $data = $request->get_json_params();
        // error_log('Acumatica Inventory Update: Received data - ' . json_encode($data));
    
        $inserted_items = $data['Inserted'] ?? [];
        if (empty($inserted_items)) {
            // error_log('Acumatica Inventory Update: No items found in the Inserted section.');
            return new WP_REST_Response(['success' => false, 'message' => 'No items found in the Inserted section.'], 400);
        }
    
        foreach ($inserted_items as $item) {
            $sku = trim($item['InventoryID']);
            $new_qty = (int) $item['QtyAvailable'];
    
            // Assuming the timestamp is in a format like 'm/d/Y H:i:s A', adjust if necessary
            $raw_timestamp = $item['INSiteStatus_Formula4650a353a5e94164b50af778b35a79f4'];
            $date = DateTime::createFromFormat('m/d/Y H:i:s A', $raw_timestamp);
            $mysql_timestamp = $date ? $date->format('Y-m-d H:i:s') : null;
        
            $product_id = wc_get_product_id_by_sku($sku);

            $product = wc_get_product($product_id);
            $old_qty = $product->get_stock_quantity();
            
            if (!$product_id) {
                // error_log("No product found with SKU: $sku");
                Acumatica_Inventory_Logger::log_update($sku, '', '', $mysql_timestamp, 'Sku Does not exist');
                continue;
            }
        
            // Update the product stock
            if ($new_qty <= 0) {
                $product->set_manage_stock(true);
                $product->set_stock_quantity(0);
                $product->set_stock_status('outofstock');
                $product->save();

                $inventoy_status = 'Inventory marked out of stock. Acumatica sent ' . $new_qty;
                $new_qty = 0;

            } elseif ($new_qty > 0) {
                if ($old_qty == $new_qty) {
                    $inventoy_status = 'No change in inventory level';
                } else {
                    $product->set_manage_stock(true);
                    $product->set_stock_quantity($new_qty);
                    $product->set_stock_status('instock');
                    $inventoy_status = 'Inventory updated successfully';
                    $product->save();
                }
            }            
    
            // Log the update with both old and new quantities, and the formatted timestamp
            Acumatica_Inventory_Logger::log_update($sku, $old_qty, $new_qty, $mysql_timestamp, $inventoy_status);
            // error_log("Updated and logged SKU: $sku from Old Qty: $old_qty to New Qty: $new_qty at $mysql_timestamp");
        }
    
        return new WP_REST_Response(['success' => true], 200);
    }
        
}
