<?php
namespace FoodFlow\Database\Migrations;

class CreateOTPTable {
    public static function run() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_otp_verification';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            otp_code varchar(10) NOT NULL,
            type varchar(50) NOT NULL,
            expires_at datetime NOT NULL,
            used tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY otp_code (otp_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}