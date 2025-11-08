<?php
namespace FoodFlow\Core;

use FoodFlow\Utils\Constants;

class JWTManager {
    
    public static function generateToken($user_id, $user_email) {
        $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : '3?=Z_kUYs%1L5%JhjC2}$5vTEwNkCA(ji@)UIaQu_9J&V!lC4&D}>!-qSW}z|B7F';
        
        $issued_at = time();
        $expire = $issued_at + (Constants::JWT_EXPIRY_HOURS * HOUR_IN_SECONDS);
        
        $payload = [
            'iss' => get_bloginfo('url'),
            'iat' => $issued_at,
            'exp' => $expire,
            'nbf' => $issued_at,
            'data' => [
                'user' => [
                    'id' => $user_id,
                    'email' => $user_email
                ]
            ]
        ];
        
        return \Firebase\JWT\JWT::encode($payload, $secret_key, 'HS256');
    }
    
    public static function validateToken($token) {
        if (empty($token)) {
            return false;
        }
        
        try {
            $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : '3?=Z_kUYs%1L5%JhjC2}$5vTEwNkCA(ji@)UIaQu_9J&V!lC4&D}>!-qSW}z|B7F';
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            
            if ($decoded && isset($decoded->data->user->id)) {
                return (object) [
                    'user_id' => $decoded->data->user->id,
                    'user_email' => $decoded->data->user->email ?? ''
                ];
            }
        } catch (\Exception $e) {
            error_log('JWT Validation Error: ' . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    public static function getUserIdFromToken($token) {
        $data = self::validateToken($token);
        return $data ? $data->user_id : false;
    }
}