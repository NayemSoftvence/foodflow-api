<?php
namespace FoodFlow\Modules\Wishlist;

class WishlistRoutes {
    
    private $controller;
    
    public function __construct() {
        $this->controller = new WishlistController();
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // Add to wishlist
        register_rest_route('foodflow/v1', '/wishlist/add', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'addToWishlist'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Remove from wishlist
        register_rest_route('foodflow/v1', '/wishlist/remove', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'removeFromWishlist'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Get wishlist items
        register_rest_route('foodflow/v1', '/wishlist', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'getWishlist'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Check if product is in wishlist
        register_rest_route('foodflow/v1', '/wishlist/check', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'checkWishlist'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Get wishlist count
        register_rest_route('foodflow/v1', '/wishlist/count', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'getWishlistCount'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Clear entire wishlist
        register_rest_route('foodflow/v1', '/wishlist/clear', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'clearWishlist'],
                'permission_callback' => '__return_true'
            ]
        ]);
    }
}