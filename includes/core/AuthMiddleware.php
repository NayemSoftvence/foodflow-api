// <?php
// namespace FoodFlow\Core;

// use FoodFlow\Utils\Constants;

// class AuthMiddleware {
    
//     public static function authenticate($request) {
//         $auth_header = $request->get_header('Authorization');
        
//         error_log("AuthMiddleware: Authorization header: " . ($auth_header ? 'PRESENT' : 'MISSING'));
        
//         if (empty($auth_header)) {
//             error_log("AuthMiddleware: No authorization header");
//             return false;
//         }
        
//         $token = self::extractTokenFromHeader($auth_header);
//         error_log("AuthMiddleware: Extracted token: " . ($token ? substr($token, 0, 20) . "..." : 'EMPTY'));
        
//         if (empty($token)) {
//             error_log("AuthMiddleware: No token extracted");
//             return false;
//         }
        
//         $user_data = JWTManager::validateToken($token);
//         error_log("AuthMiddleware: User data from token: " . ($user_data ? "User ID: " . $user_data->user_id : 'INVALID'));
        
//         if (!$user_data) {
//             error_log("AuthMiddleware: Token validation failed");
//             return false;
//         }
        
//         // Verify user exists
//         $user = get_user_by('id', $user_data->user_id);
//         if (!$user) {
//             error_log("AuthMiddleware: User ID {$user_data->user_id} not found in database");
//             return false;
//         }
        
//         error_log("AuthMiddleware: User found - ID: {$user->ID}, Email: {$user->user_email}");
        
//         // Set user data on request for use in controllers
//         $request->set_param('current_user_id', $user_data->user_id);
//         $request->set_param('current_user_email', $user_data->user_email);
        
//         error_log("AuthMiddleware: Parameters set - current_user_id: " . $request->get_param('current_user_id'));
        
//         return true;
//     }
    
//     private static function extractTokenFromHeader($auth_header) {
//         if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
//             return trim($matches[1]);
//         }
//         return null;
//     }
    
//     public static function permission_callback() {
//         return function($request) {
//             $result = self::authenticate($request);
//             error_log("Permission callback result: " . ($result ? 'TRUE' : 'FALSE'));
//             return $result;
//         };
//     }
// }