<?php
namespace FoodFlow\Modules\Products;

class ProductRoutes {
    
    private $controller;
    
    public function __construct() {
        $this->controller = new ProductController();
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // Get products list with filters
        register_rest_route('foodflow/v1', '/products', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'getProducts'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Get single product details
        register_rest_route('foodflow/v1', '/products/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'getProduct'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Get categories
        register_rest_route('foodflow/v1', '/products/categories', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'getCategories'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Search products
        register_rest_route('foodflow/v1', '/products/search', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'searchProducts'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Get available filters
        register_rest_route('foodflow/v1', '/products/filters', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'getFilters'],
                'permission_callback' => '__return_true'
            ]
        ]);
    }
}