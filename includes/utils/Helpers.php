<?php
namespace FoodFlow\Utils;

class Helpers {
    
    public static function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return $ip;
    }
    
    public static function generateUniqueId($length = 16) {
        return bin2hex(random_bytes($length));
    }
    
    public static function formatTimestamp($timestamp) {
        return date('Y-m-d H:i:s', $timestamp);
    }
}