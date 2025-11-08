<?php
namespace FoodFlow\Core;

class ApiResponse {
    
    public static function success($data = null, $message = 'Success', $code = 200) {
        return self::formatResponse(true, $message, $data, $code);
    }
    
    public static function error($message = 'Error', $code = 400, $errors = []) {
        return self::formatResponse(false, $message, null, $code, $errors);
    }
    
    public static function validationError($errors = [], $message = 'Validation failed') {
        return self::error($message, 422, $errors);
    }
    
    private static function formatResponse($success, $message, $data, $code, $errors = []) {
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return new \WP_REST_Response($response, $code);
    }
}