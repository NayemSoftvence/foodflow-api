<?php
namespace FoodFlow\Modules\Products;

use FoodFlow\Core\ApiResponse;
use FoodFlow\Core\Validator;
use FoodFlow\Utils\Constants;

class ProductController {
    
    public function getProducts($request) {
        $params = $request->get_params();
        
        $validation = Validator::validate($params, [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
            'category' => 'string',
            'search' => 'string',
            'orderby' => 'string',
            'order' => 'string|in:asc,desc'
        ]);
        
        if (!$validation['success']) {
            return ApiResponse::validationError($validation['errors']);
        }
        
        $products_data = ProductRepository::getProducts($params);
        
        return ApiResponse::success($products_data, 'Products retrieved successfully');
    }
    
    public function getProduct($request) {
        $product_id = $request->get_param('id');
        
        if (empty($product_id)) {
            return ApiResponse::error('Product ID is required', Constants::BAD_REQUEST);
        }
        
        $product = ProductRepository::getProductById($product_id);
        
        if (!$product) {
            return ApiResponse::error('Product not found', Constants::NOT_FOUND);
        }
        
        return ApiResponse::success([
            'product' => $product
        ], 'Product retrieved successfully');
    }
    
    public function getCategories($request) {
        $categories = ProductRepository::getCategories();
        
        return ApiResponse::success([
            'categories' => $categories
        ], 'Categories retrieved successfully');
    }
    
    public function searchProducts($request) {
        $params = $request->get_params();
        $search_term = $params['q'] ?? '';
        
        if (empty($search_term)) {
            return ApiResponse::error('Search term is required', Constants::BAD_REQUEST);
        }
        
        $limit = $params['limit'] ?? 10;
        
        $products = ProductRepository::searchProducts($search_term, $limit);
        
        return ApiResponse::success([
            'products' => $products,
            'search_term' => $search_term,
            'count' => count($products)
        ], 'Search completed successfully');
    }
    
    public function getFilters($request) {
        $filters = [
            'sort_options' => [
                ['value' => 'date', 'label' => 'Newest'],
                ['value' => 'price', 'label' => 'Price: Low to High'],
                ['value' => 'price-desc', 'label' => 'Price: High to Low'],
                ['value' => 'rating', 'label' => 'Highest Rated'],
                ['value' => 'popularity', 'label' => 'Most Popular']
            ],
            'price_ranges' => [
                ['min' => 0, 'max' => 25, 'label' => 'Under $25'],
                ['min' => 25, 'max' => 50, 'label' => '$25 to $50'],
                ['min' => 50, 'max' => 100, 'label' => '$50 to $100'],
                ['min' => 100, 'max' => 0, 'label' => 'Over $100']
            ]
        ];
        
        return ApiResponse::success($filters, 'Filters retrieved successfully');
    }
}