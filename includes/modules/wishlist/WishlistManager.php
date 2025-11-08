<?php
namespace FoodFlow\Modules\Wishlist;

use FoodFlow\Core\DatabaseManager;

class WishlistManager {
    
    /**
     * Add product to user's wishlist
     */
    public static function addToWishlist($user_id, $product_id) {
        // Check if product exists
        if (!self::productExists($product_id)) {
            return false;
        }
        
        return DatabaseManager::addToWishlist($user_id, $product_id);
    }
    
    /**
     * Remove product from user's wishlist
     */
    public static function removeFromWishlist($user_id, $product_id) {
        return DatabaseManager::removeFromWishlist($user_id, $product_id);
    }
    
    /**
     * Get user's wishlist items with product details
     */
    public static function getWishlist($user_id, $page = 1, $per_page = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'foodflow_wishlist';
        $offset = ($page - 1) * $per_page;
        
        // Get wishlist items with pagination
        $items = $wpdb->get_results($wpdb->prepare("
            SELECT w.*, p.post_title as product_name 
            FROM $table_name w
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
            WHERE w.user_id = %d AND p.post_status = 'publish'
            ORDER BY w.created_at DESC
            LIMIT %d OFFSET %d
        ", $user_id, $per_page, $offset));
        
        // Get total count for pagination
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $table_name w
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
            WHERE w.user_id = %d AND p.post_status = 'publish'
        ", $user_id));
        
        $wishlist_items = [];
        foreach ($items as $item) {
            $product = wc_get_product($item->product_id);
            if ($product && $product->get_status() === 'publish') {
                $wishlist_items[] = self::formatWishlistItem($item, $product);
            }
        }
        
        return [
            'items' => $wishlist_items,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$per_page,
                'total_items' => (int)$total,
                'total_pages' => ceil($total / $per_page),
                'has_next' => $page < ceil($total / $per_page),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Check if product is in user's wishlist
     */
    public static function isInWishlist($user_id, $product_id) {
        return DatabaseManager::isInWishlist($user_id, $product_id);
    }
    
    /**
     * Get wishlist count for user
     */
    public static function getWishlistCount($user_id) {
        return DatabaseManager::getWishlistCount($user_id);
    }
    
    /**
     * Clear user's entire wishlist
     */
    public static function clearWishlist($user_id) {
        return DatabaseManager::clearWishlist($user_id);
    }
    
    /**
     * Format wishlist item with product details
     */
    private static function formatWishlistItem($wishlist_item, $product) {
        $image_id = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src();
        
        return [
            'wishlist_id' => (int)$wishlist_item->id,
            'product_id' => (int)$wishlist_item->product_id,
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'image' => $image_url,
            'stock_status' => $product->get_stock_status(),
            'in_stock' => $product->is_in_stock(),
            'rating' => $product->get_average_rating(),
            'review_count' => $product->get_review_count(),
            'added_date' => $wishlist_item->created_at
        ];
    }
    
    /**
     * Check if product exists and is published
     */
    private static function productExists($product_id) {
        $product = wc_get_product($product_id);
        return $product && $product->get_status() === 'publish';
    }
}