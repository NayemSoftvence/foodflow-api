<?php
namespace FoodFlow\Database\Migrations;

class UpdateCartTable {
    public static function run() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_cart';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            error_log("UpdateCartTable: Cart table doesn't exist, creating it first");
            CreateCartTable::run();
            return;
        }
        
        // Check if user_id already allows NULL
        $column_info = $wpdb->get_row("SHOW COLUMNS FROM $table_name WHERE Field = 'user_id'");
        
        if ($column_info && strpos($column_info->Type, 'NO') !== false) {
            // user_id is set to NOT NULL, we need to alter it
            error_log("UpdateCartTable: Altering user_id to allow NULL");
            
            $sql = "ALTER TABLE $table_name 
                    MODIFY user_id bigint(20) NULL,
                    MODIFY session_key varchar(100) NULL,
                    ADD UNIQUE KEY user_product_session (user_id, product_id, session_key)";
            
            $result = $wpdb->query($sql);
            error_log("UpdateCartTable: Alter table result: " . ($result ? 'SUCCESS' : 'FAILED - ' . $wpdb->last_error));
        } else {
            error_log("UpdateCartTable: user_id already allows NULL or table doesn't need update");
        }
    }
}