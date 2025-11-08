<?php
/**
 * Plugin Name: FoodFlow API
 * Description: Custom REST API for FoodFlow Flutter App
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FOODFLOW_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FOODFLOW_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include core files
require_once FOODFLOW_PLUGIN_PATH . 'includes/core/ApiResponse.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/core/Validator.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/core/JWTManager.php';

require_once FOODFLOW_PLUGIN_PATH . 'includes/database/DatabaseManager.php';

// Include utility files
require_once FOODFLOW_PLUGIN_PATH . 'includes/utils/Constants.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/utils/Security.php';

// Include authentication module
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/auth/OTPManager.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/auth/EmailService.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/auth/AuthController.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/auth/AuthRoutes.php';

// Include user module
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/user/UserController.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/user/UserRoutes.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/database/migrations/CreateOTPTable.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/database/migrations/CreateUserSessionsTable.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/database/migrations/CreateWishlistTable.php';



// Include products module
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/products/ProductRepository.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/products/ProductController.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/products/ProductRoutes.php';
// Include wishlist module
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/wishlist/WishlistManager.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/wishlist/WishlistController.php';
require_once FOODFLOW_PLUGIN_PATH . 'includes/modules/wishlist/WishlistRoutes.php';


// Whitelist our endpoints for JWT Authentication plugin
add_filter('jwt_auth_whitelist', function($endpoints) {
    $our_endpoints = [
        '/foodflow/v1/auth/register',
        '/foodflow/v1/auth/login', 
        '/foodflow/v1/auth/verify-otp',
        '/foodflow/v1/auth/resend-otp',
        '/foodflow/v1/auth/forgot-password',
        '/foodflow/v1/auth/reset-password',
        '/foodflow/v1/auth/refresh-token',
        '/foodflow/v1/auth/logout',
        '/foodflow/v1/test',
        '/foodflow/v1/health'
    ];
    
    return array_unique(array_merge($endpoints, $our_endpoints));
});

// Main Plugin Class
class FoodFlowAPI {
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('init', [$this, 'check_tables']);
        
        // Initialize modules
        $this->init_modules();
    }
    
    private function init_modules() {
        new FoodFlow\Modules\Auth\AuthRoutes();
        new FoodFlow\Modules\User\UserRoutes();
        new FoodFlow\Modules\Products\ProductRoutes();
        new FoodFlow\Modules\Wishlist\WishlistRoutes();
    
    }
    
    public function register_routes() {
        
        register_rest_route('foodflow/v1', '/debug/decode-token', [
        'methods' => 'POST',
        'callback' => [$this, 'debug_decode_token'],
        'permission_callback' => '__return_true'
]);
        // Test endpoint
        register_rest_route('foodflow/v1', '/test', [
            'methods' => 'GET',
            'callback' => [$this, 'test_endpoint'],
            'permission_callback' => '__return_true'
        ]);
        
        // Health check endpoint
        register_rest_route('foodflow/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    public function test_endpoint($request) {
        return FoodFlow\Core\ApiResponse::success([
            'message' => 'FoodFlow API with JWT & User Profile!',
            'timestamp' => current_time('mysql'),
            'step' => 'Step 4: JWT Auth & User Profile',
            'features' => [
                'JWT Token Authentication',
                'Protected Routes', 
                'User Profile Management',
                'Password Change',
                'Account Deletion'
            ]
        ]);
    }
    
    public function debug_decode_token($request) {
    $data = $request->get_json_params();
    $token = $data['token'] ?? '';
    
    if (empty($token)) {
        return \FoodFlow\Core\ApiResponse::error('Token required');
    }
    
    try {
        // Manually decode the token to see what's inside
        $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : '3?=Z_kUYs%1L5%JhjC2}$5vTEwNkCA(ji@)UIaQu_9J&V!lC4&D}>!-qSW}z|B7F';
        $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
        
        $result = [
            'token_decoded' => $decoded,
            'user_id_in_token' => $decoded->data->user->id ?? 'NOT_FOUND',
            'user_email_in_token' => $decoded->data->user->email ?? 'NOT_FOUND',
            'token_issuer' => $decoded->iss ?? 'NOT_FOUND',
            'token_expiry' => date('Y-m-d H:i:s', $decoded->exp ?? 0),
            'current_time' => date('Y-m-d H:i:s', time())
        ];
        
        // Check if user exists
        $user_id = $decoded->data->user->id ?? 0;
        if ($user_id) {
            $user = get_userdata($user_id);
            $result['user_exists'] = (bool) $user;
            $result['user_details'] = $user ? [
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'display_name' => $user->display_name
            ] : 'USER_NOT_FOUND';
        }
        
        return \FoodFlow\Core\ApiResponse::success($result, 'Token decoded successfully');
        
    } catch (\Exception $e) {
        return \FoodFlow\Core\ApiResponse::error('Token decode failed: ' . $e->getMessage());
    }
}
    
    public function health_check($request) {
        global $wpdb;
        
        $health_status = [
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'database_tables' => $this->check_database_tables(),
            'modules' => [
                'Auth' => 'Active',
                'User Profile' => 'Active',
                'JWT' => 'Active',
                'OTP' => 'Active'
            ]
        ];
        
        return FoodFlow\Core\ApiResponse::success($health_status, 'FoodFlow API Health Check');
    }
    
    private function check_database_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'foodflow_otp_verification' => false,
            $wpdb->prefix . 'foodflow_user_sessions' => false
        ];
        
        foreach ($tables as $table_name => $value) {
            $tables[$table_name] = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        }
        
        return $tables;
    }
    
    public function check_tables() {
        FoodFlow\Core\DatabaseManager::runMigrations();
    }
}

// Initialize the plugin
new FoodFlowAPI();

// Activation hook
register_activation_hook(__FILE__, function() {
    FoodFlow\Core\DatabaseManager::runMigrations();
});