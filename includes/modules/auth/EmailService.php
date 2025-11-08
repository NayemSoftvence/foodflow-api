<?php
namespace FoodFlow\Modules\Auth;

class EmailService {
    
    public static function sendVerificationEmail($email, $name, $otp) {
        $subject = 'Verify Your FoodFlow Account';
        $message = "
            <h2>Welcome to FoodFlow, $name!</h2>
            <p>Your verification code is: <strong>$otp</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't create an account, please ignore this email.</p>
        ";
        
        return self::sendEmail($email, $subject, $message);
    }
    
    public static function sendPasswordResetEmail($email, $name, $otp) {
        $subject = 'Reset Your FoodFlow Password';
        $message = "
            <h2>Password Reset Request</h2>
            <p>Hello $name,</p>
            <p>Your password reset code is: <strong>$otp</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request a password reset, please ignore this email.</p>
        ";
        
        return self::sendEmail($email, $subject, $message);
    }
    
    private static function sendEmail($to, $subject, $message) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: FoodFlow <noreply@foodflow.com>'
        ];
        
        return wp_mail($to, $subject, $message, $headers);
    }
}