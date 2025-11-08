<?php
namespace FoodFlow\Modules\Auth;

use FoodFlow\Core\ApiResponse;
use FoodFlow\Core\Validator;
use FoodFlow\Core\JWTManager;
use FoodFlow\Utils\Security;
use FoodFlow\Utils\Constants;

class AuthController {
    
    public function register($request) {
        $data = $request->get_json_params();
        
        // Validation
        $validation = Validator::validate($data, [
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone' => 'required|string'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        // Create user
        $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), Constants::BAD_REQUEST);
        }
        
        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'display_name' => Security::sanitizeInput($data['name'])
        ]);
        
        update_user_meta($user_id, 'phone', Security::sanitizeInput($data['phone']));
        
        // Generate JWT token
        $token = JWTManager::generateToken($user_id, $data['email']);
        
        return ApiResponse::success([
            'user' => [
                'id' => $user_id,
                'name' => Security::sanitizeInput($data['name']),
                'email' => $data['email'],
                'phone' => Security::sanitizeInput($data['phone'])
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 24 * 3600
        ], 'Registration successful', Constants::CREATED);
    }
    
    public function login($request) {
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        // WordPress authentication
        $user = wp_authenticate($data['email'], $data['password']);
        
        if (is_wp_error($user)) {
            return ApiResponse::error('Invalid credentials', Constants::UNAUTHORIZED);
        }
        
        // Generate JWT token
        $token = JWTManager::generateToken($user->ID, $user->user_email);
        
        return ApiResponse::success([
            'user' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => get_user_meta($user->ID, 'phone', true)
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 24 * 3600
        ], 'Login successful');
    }
    
    public function verifyOTP($request) {
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'type' => 'required|string'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $user = get_user_by('email', $data['email']);
        if (!$user) {
            return ApiResponse::error('User not found', Constants::NOT_FOUND);
        }
        
        $verified = OTPManager::verifyOTP($user->ID, $data['otp'], $data['type']);
        
        if (!$verified) {
            return ApiResponse::error('Invalid or expired OTP', Constants::UNAUTHORIZED);
        }
        
        return ApiResponse::success(null, 'OTP verified successfully');
    }
    
    public function resendOTP($request) {
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'email' => 'required|email',
            'type' => 'required|string'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $user = get_user_by('email', $data['email']);
        if (!$user) {
            return ApiResponse::error('User not found', Constants::NOT_FOUND);
        }
        
        $otp = OTPManager::resendOTP($user->ID, $data['type']);
        
        if (!$otp) {
            return ApiResponse::error('Failed to generate OTP', Constants::SERVER_ERROR);
        }
        
        return ApiResponse::success([
            'otp_code' => $otp // Remove this in production - only for testing
        ], 'OTP sent successfully');
    }
    
    public function forgotPassword($request) {
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'email' => 'required|email'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $user = get_user_by('email', $data['email']);
        if (!$user) {
            // Don't reveal if email exists for security
            return ApiResponse::success(null, 'If the email exists, a reset code has been sent.');
        }
        
        // Generate OTP for password reset
        $otp = OTPManager::generateOTP($user->ID, 'password_reset');
        
        return ApiResponse::success([
            'otp_code' => $otp // Remove this in production - only for testing
        ], 'If the email exists, a reset code has been sent.');
    }
    
    public function resetPassword($request) {
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'new_password' => 'required|min:6'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $user = get_user_by('email', $data['email']);
        if (!$user) {
            return ApiResponse::error('User not found', Constants::NOT_FOUND);
        }
        
        // Verify OTP
        $verified = OTPManager::verifyOTP($user->ID, $data['otp'], 'password_reset');
        
        if (!$verified) {
            return ApiResponse::error('Invalid or expired OTP', Constants::UNAUTHORIZED);
        }
        
        // Reset password
        wp_set_password($data['new_password'], $user->ID);
        
        return ApiResponse::success(null, 'Password reset successfully');
    }
    
    public function logout($request) {
        return ApiResponse::success(null, 'Logged out successfully');
    }
    
    public function refreshToken($request) {
        $auth_header = $request->get_header('Authorization');
        $token = self::extractTokenFromHeader($auth_header);
        
        if (empty($token)) {
            return ApiResponse::error('Token required', Constants::UNAUTHORIZED);
        }
        
        $user_data = JWTManager::validateToken($token);
        
        if (!$user_data) {
            return ApiResponse::error('Invalid token', Constants::UNAUTHORIZED);
        }
        
        // Generate new token
        $new_token = JWTManager::generateToken($user_data->user_id, $user_data->user_email);
        
        return ApiResponse::success([
            'token' => $new_token,
            'token_type' => 'Bearer',
            'expires_in' => 24 * 3600
        ], 'Token refreshed successfully');
    }
    
    private static function extractTokenFromHeader($auth_header) {
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}