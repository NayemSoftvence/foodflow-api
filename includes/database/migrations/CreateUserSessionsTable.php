<?php
namespace FoodFlow\Database\Migrations;

class CreateUserSessionsTable {
    public static function run() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_user_sessions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            device_id varchar(255),
            jwt_token text,
            refresh_token text,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY device_id (device_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}