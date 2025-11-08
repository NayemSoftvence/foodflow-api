<?php
namespace FoodFlow\Modules\Auth;

use FoodFlow\Core\DatabaseManager;
use FoodFlow\Utils\Constants;

class OTPManager {
    
    public static function generateOTP($user_id, $type = 'email_verification') {
        $otp = self::generateRandomOTP();
        $expires_at = date('Y-m-d H:i:s', time() + (Constants::OTP_EXPIRY_MINUTES * 60));
        
        // Store in database
        $result = DatabaseManager::storeOTP($user_id, $otp, $type, $expires_at);
        
        if ($result) {
            return $otp;
        }
        
        return false;
    }
    
    public static function verifyOTP($user_id, $otp, $type) {
        return DatabaseManager::verifyOTP($user_id, $otp, $type);
    }
    
    public static function resendOTP($user_id, $type) {
        // Invalidate existing OTP
        DatabaseManager::invalidateOTP($user_id, $type);
        
        // Generate new OTP
        return self::generateOTP($user_id, $type);
    }
    
    private static function generateRandomOTP() {
        return str_pad(rand(0, pow(10, Constants::OTP_LENGTH) - 1), Constants::OTP_LENGTH, '0', STR_PAD_LEFT);
    }
}