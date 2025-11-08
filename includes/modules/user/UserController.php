<?php
namespace FoodFlow\Modules\User;

use FoodFlow\Core\ApiResponse;
use FoodFlow\Core\Validator;
use FoodFlow\Core\JWTManager;
use FoodFlow\Utils\Security;
use FoodFlow\Utils\Constants;

class UserController {
    
    public function getProfile($request) {
        // Authenticate user directly from token
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        error_log("UserController: Authenticated user ID: " . $user_id);
        
        $user = get_userdata($user_id);
        if (!$user) {
            return ApiResponse::error('User not found', Constants::NOT_FOUND);
        }
        
        $profile_data = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user->ID, 'phone', true),
            'registered_date' => $user->user_registered
        ];
        
        return ApiResponse::success([
            'user' => $profile_data
        ], 'Profile retrieved successfully');
    }
    
    public function updateProfile($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'name' => 'string|min:2',
            'phone' => 'string'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $user_data = ['ID' => $user_id];
        
        if (!empty($data['name'])) {
            $user_data['display_name'] = Security::sanitizeInput($data['name']);
        }
        
        $result = wp_update_user($user_data);
        
        if (is_wp_error($result)) {
            return ApiResponse::error($result->get_error_message(), Constants::BAD_REQUEST);
        }
        
        if (!empty($data['phone'])) {
            update_user_meta($user_id, 'phone', Security::sanitizeInput($data['phone']));
        }
        
        return ApiResponse::success(null, 'Profile updated successfully');
    }
    
    public function changePassword($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'current_password' => 'required',
            'new_password' => 'required|min:6'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $user = get_userdata($user_id);
        if (!wp_check_password($data['current_password'], $user->user_pass, $user_id)) {
            return ApiResponse::error('Current password is incorrect', Constants::UNAUTHORIZED);
        }
        
        wp_set_password($data['new_password'], $user_id);
        
        return ApiResponse::success(null, 'Password changed successfully');
    }
    
    public function deleteAccount($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $data = $request->get_json_params();
        
        if (!empty($data['password'])) {
            $user = get_userdata($user_id);
            if (!wp_check_password($data['password'], $user->user_pass, $user_id)) {
                return ApiResponse::error('Password is incorrect', Constants::UNAUTHORIZED);
            }
        }
        
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $result = wp_delete_user($user_id);
        
        if (!$result) {
            return ApiResponse::error('Failed to delete account', Constants::SERVER_ERROR);
        }
        
        return ApiResponse::success(null, 'Account deleted successfully');
    }
    
    /**
     * Authenticate the request and return user ID or WP_Error
     */
    private function authenticateRequest($request) {
        $auth_header = $request->get_header('Authorization');
        
        error_log("UserController: Authentication started");
        error_log("UserController: Authorization header: " . ($auth_header ? 'PRESENT' : 'MISSING'));
        
        if (empty($auth_header)) {
            error_log("UserController: No authorization header");
            return new \WP_Error('missing_authorization', 'Authorization header missing', ['status' => Constants::UNAUTHORIZED]);
        }
        
        $token = $this->extractTokenFromHeader($auth_header);
        error_log("UserController: Extracted token: " . ($token ? substr($token, 0, 20) . "..." : 'EMPTY'));
        
        if (empty($token)) {
            error_log("UserController: No token extracted");
            return new \WP_Error('invalid_authorization', 'Invalid authorization format', ['status' => Constants::UNAUTHORIZED]);
        }
        
        $user_data = JWTManager::validateToken($token);
        error_log("UserController: User data from token: " . ($user_data ? "User ID: " . $user_data->user_id : 'INVALID'));
        
        if (!$user_data) {
            error_log("UserController: Token validation failed");
            return new \WP_Error('invalid_token', 'Invalid or expired token', ['status' => Constants::UNAUTHORIZED]);
        }
        
        $user = get_user_by('id', $user_data->user_id);
        if (!$user) {
            error_log("UserController: User ID {$user_data->user_id} not found in database");
            return new \WP_Error('user_not_found', 'User not found', ['status' => Constants::NOT_FOUND]);
        }
        
        error_log("UserController: Authentication successful for user ID: " . $user_data->user_id);
        
        return $user_data->user_id;
    }
    
    private function extractTokenFromHeader($auth_header) {
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}