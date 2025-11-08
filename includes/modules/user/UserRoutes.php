<?php
namespace FoodFlow\Modules\User;

class UserRoutes {
    
    private $controller;
    
    public function __construct() {
        $this->controller = new UserController();
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // All user routes are public - authentication is handled in controller
        register_rest_route('foodflow/v1', '/user/profile', [
            [
                'methods' => 'GET',
                'callback' => [$this->controller, 'getProfile'],
                'permission_callback' => '__return_true' // Public - auth handled in controller
            ]
        ]);
        
        register_rest_route('foodflow/v1', '/user/profile', [
            [
                'methods' => 'PUT',
                'callback' => [$this->controller, 'updateProfile'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        register_rest_route('foodflow/v1', '/user/change-password', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'changePassword'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        register_rest_route('foodflow/v1', '/user/delete-account', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'deleteAccount'],
                'permission_callback' => '__return_true'
            ]
        ]);
    }
}