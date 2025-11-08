<?php
namespace FoodFlow\Database\Migrations;

private static function createCartTable() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'foodflow_cart';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,  // CHANGED: REQUIRED for authenticated users only
            product_id bigint(20) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_product (user_id, product_id),  // ADDED: Prevent duplicates
            KEY user_id (user_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log("FoodFlow: Cart table created with REQUIRED user_id");
    }
}