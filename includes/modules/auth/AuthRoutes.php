<?php
namespace FoodFlow\Modules\Auth;

use FoodFlow\Core\AuthMiddleware;

class AuthRoutes {
    
    private $controller;
    
    public function __construct() {
        $this->controller = new AuthController();
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // Registration
        register_rest_route('foodflow/v1', '/auth/register', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'register'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Login
        register_rest_route('foodflow/v1', '/auth/login', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'login'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // OTP Verification (now only for password reset)
        register_rest_route('foodflow/v1', '/auth/verify-otp', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'verifyOTP'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Resend OTP (now only for password reset)
        register_rest_route('foodflow/v1', '/auth/resend-otp', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'resendOTP'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Forgot Password
        register_rest_route('foodflow/v1', '/auth/forgot-password', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'forgotPassword'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Reset Password
        register_rest_route('foodflow/v1', '/auth/reset-password', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'resetPassword'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Logout
        register_rest_route('foodflow/v1', '/auth/logout', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'logout'],
                'permission_callback' => '__return_true'
            ]
        ]);
        
        // Token Refresh
        register_rest_route('foodflow/v1', '/auth/refresh-token', [
            [
                'methods' => 'POST',
                'callback' => [$this->controller, 'refreshToken'],
                'permission_callback' => '__return_true'
            ]
        ]);
    }
}