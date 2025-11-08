<?php
namespace FoodFlow\Core;

class DatabaseManager {
    
    /**
     * Run all database migrations
     */
    public static function runMigrations() {
        // Run each migration file
        self::runMigration('CreateOTPTable');
        self::runMigration('CreateUserSessionsTable');
        self::runMigration('CreateWishlistTable');
        self::runMigration('CreateCartTable');
        
        error_log('FoodFlow: All database migrations completed');
    }
    
    /**
     * Run a specific migration
     */
    private static function runMigration($migration_class) {
        $class_name = "FoodFlow\\Database\\Migrations\\{$migration_class}";
        
        if (class_exists($class_name)) {
            try {
                $class_name::run();
                error_log("FoodFlow: Migration {$migration_class} executed successfully");
            } catch (\Exception $e) {
                error_log("FoodFlow: Migration {$migration_class} failed: " . $e->getMessage());
            }
        } else {
            error_log("FoodFlow: Migration class {$class_name} not found");
        }
    }
    
    /**
     * OTP Management Methods
     * These stay in DatabaseManager since they're business logic, not schema
     */
    public static function storeOTP($user_id, $otp, $type, $expires_at) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_otp_verification';
        
        // Invalidate any existing OTP for this user and type
        self::invalidateOTP($user_id, $type);
        
        return $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'otp_code' => $otp,
                'type' => $type,
                'expires_at' => $expires_at,
                'used' => 0
            ],
            ['%d', '%s', '%s', '%s', '%d']
        );
    }
    
    public static function verifyOTP($user_id, $otp, $type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_otp_verification';
        
        $current_time = current_time('mysql');
        
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE user_id = %d AND otp_code = %s AND type = %s AND used = 0 AND expires_at > %s
        ", $user_id, $otp, $type, $current_time));
        
        if ($result) {
            // Mark OTP as used
            $wpdb->update(
                $table_name,
                ['used' => 1],
                ['id' => $result->id],
                ['%d'],
                ['%d']
            );
            return true;
        }
        
        return false;
    }
    
    public static function invalidateOTP($user_id, $type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_otp_verification';
        
        return $wpdb->update(
            $table_name,
            ['used' => 1],
            ['user_id' => $user_id, 'type' => $type, 'used' => 0],
            ['%d'],
            ['%d', '%s', '%d']
        );
    }
    
    /**
     * Wishlist Management Methods
     * These stay in DatabaseManager since they're business logic
     */
    public static function addToWishlist($user_id, $product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_wishlist';
        
        // Check if already in wishlist
        if (self::isInWishlist($user_id, $product_id)) {
            return true; // Already exists
        }
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'product_id' => $product_id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );
        
        return $result !== false;
    }
    
    public static function removeFromWishlist($user_id, $product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_wishlist';
        
        $result = $wpdb->delete(
            $table_name,
            [
                'user_id' => $user_id,
                'product_id' => $product_id
            ],
            ['%d', '%d']
        );
        
        return $result !== false;
    }
    
    public static function isInWishlist($user_id, $product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_wishlist';
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $table_name 
            WHERE user_id = %d AND product_id = %d
        ", $user_id, $product_id));
        
        return $count > 0;
    }
    
    public static function getWishlistCount($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_wishlist';
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $table_name w
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
            WHERE w.user_id = %d AND p.post_status = 'publish'
        ", $user_id));
    }
    
    public static function clearWishlist($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_wishlist';
        
        $result = $wpdb->delete(
            $table_name,
            ['user_id' => $user_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Utility Methods
     */
    public static function tableExists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }
    
    public static function getTablePrefix() {
        global $wpdb;
        return $wpdb->prefix;
    }
}