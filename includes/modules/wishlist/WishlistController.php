<?php
namespace FoodFlow\Modules\Wishlist;

use FoodFlow\Core\ApiResponse;
use FoodFlow\Core\Validator;
use FoodFlow\Utils\Constants;

class WishlistController {
    
    /**
     * Add item to wishlist
     */
    public function addToWishlist($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'product_id' => 'required|integer|min:1'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $product_id = $data['product_id'];
        
        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') {
            return ApiResponse::error('Product not found', Constants::NOT_FOUND);
        }
        
        $result = WishlistManager::addToWishlist($user_id, $product_id);
        
        if (!$result) {
            return ApiResponse::error('Failed to add item to wishlist', Constants::SERVER_ERROR);
        }
        
        $wishlist_count = WishlistManager::getWishlistCount($user_id);
        
        return ApiResponse::success([
            'added' => true,
            'product_id' => $product_id,
            'wishlist_count' => $wishlist_count,
            'message' => 'Product added to wishlist successfully'
        ], 'Product added to wishlist');
    }
    
    /**
     * Remove item from wishlist
     */
    public function removeFromWishlist($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $data = $request->get_json_params();
        
        $validation = Validator::validate($data, [
            'product_id' => 'required|integer|min:1'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $product_id = $data['product_id'];
        
        $result = WishlistManager::removeFromWishlist($user_id, $product_id);
        
        if (!$result) {
            return ApiResponse::error('Failed to remove item from wishlist', Constants::SERVER_ERROR);
        }
        
        $wishlist_count = WishlistManager::getWishlistCount($user_id);
        
        return ApiResponse::success([
            'removed' => true,
            'product_id' => $product_id,
            'wishlist_count' => $wishlist_count,
            'message' => 'Product removed from wishlist successfully'
        ], 'Product removed from wishlist');
    }
    
    /**
     * Get user's wishlist
     */
    public function getWishlist($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $params = $request->get_params();
        
        $validation = Validator::validate($params, [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $page = $params['page'] ?? 1;
        $per_page = $params['per_page'] ?? 20;
        
        $wishlist_data = WishlistManager::getWishlist($user_id, $page, $per_page);
        
        return ApiResponse::success($wishlist_data, 'Wishlist retrieved successfully');
    }
    
    /**
     * Check if product is in wishlist
     */
    public function checkWishlist($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $product_id = $request->get_param('product_id');
        
        if (empty($product_id)) {
            return ApiResponse::error('Product ID is required', Constants::BAD_REQUEST);
        }
        
        $in_wishlist = WishlistManager::isInWishlist($user_id, $product_id);
        $wishlist_count = WishlistManager::getWishlistCount($user_id);
        
        return ApiResponse::success([
            'in_wishlist' => $in_wishlist,
            'product_id' => (int)$product_id,
            'wishlist_count' => $wishlist_count
        ], 'Wishlist status checked successfully');
    }
    
    /**
     * Get wishlist count
     */
    public function getWishlistCount($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $count = WishlistManager::getWishlistCount($user_id);
        
        return ApiResponse::success([
            'count' => $count
        ], 'Wishlist count retrieved successfully');
    }
    
    /**
     * Clear entire wishlist
     */
    public function clearWishlist($request) {
        $user_id = $this->authenticateRequest($request);
        
        if (is_wp_error($user_id)) {
            return ApiResponse::error($user_id->get_error_message(), $user_id->get_error_data()['status'] ?? Constants::UNAUTHORIZED);
        }
        
        $result = WishlistManager::clearWishlist($user_id);
        
        if (!$result) {
            return ApiResponse::error('Failed to clear wishlist', Constants::SERVER_ERROR);
        }
        
        return ApiResponse::success([
            'cleared' => true,
            'message' => 'Wishlist cleared successfully'
        ], 'Wishlist cleared successfully');
    }
    
    /**
     * Authenticate request and return user ID
     */
    private function authenticateRequest($request) {
        $auth_header = $request->get_header('Authorization');
        
        if (empty($auth_header)) {
            return new \WP_Error('missing_authorization', 'Authorization header missing', ['status' => Constants::UNAUTHORIZED]);
        }
        
        $token = $this->extractTokenFromHeader($auth_header);
        
        if (empty($token)) {
            return new \WP_Error('invalid_authorization', 'Invalid authorization format', ['status' => Constants::UNAUTHORIZED]);
        }
        
        $user_data = \FoodFlow\Core\JWTManager::validateToken($token);
        
        if (!$user_data) {
            return new \WP_Error('invalid_token', 'Invalid or expired token', ['status' => Constants::UNAUTHORIZED]);
        }
        
        $user = get_user_by('id', $user_data->user_id);
        if (!$user) {
            return new \WP_Error('user_not_found', 'User not found', ['status' => Constants::NOT_FOUND]);
        }
        
        return $user_data->user_id;
    }
    
    private function extractTokenFromHeader($auth_header) {
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}