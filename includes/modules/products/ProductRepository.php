<?php
namespace FoodFlow\Modules\Products;

use FoodFlow\Core\ApiResponse;
use FoodFlow\Utils\Constants;

class ProductRepository {
    
    public static function getProducts($params = []) {
        $page = $params['page'] ?? 1;
        $per_page = $params['per_page'] ?? 12;
        $category = $params['category'] ?? '';
        $search = $params['search'] ?? '';
        $orderby = $params['orderby'] ?? 'date';
        $order = $params['order'] ?? 'desc';
        
        $args = [
            'status' => 'publish',
            'limit' => $per_page,
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'return' => 'ids'
        ];
        
        // Add category filter
        if (!empty($category)) {
            $args['category'] = [$category];
        }
        
        // Add search filter
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        try {
            $products = wc_get_products($args);
            $total_products = self::getTotalProductsCount($args);
            $total_pages = ceil($total_products / $per_page);
            
            $formatted_products = [];
            foreach ($products as $product_id) {
                $formatted_products[] = self::formatProductData($product_id);
            }
            
            return [
                'products' => $formatted_products,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$per_page,
                    'total_products' => $total_products,
                    'total_pages' => $total_pages,
                    'has_next' => $page < $total_pages,
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (\Exception $e) {
            error_log('ProductRepository Error: ' . $e->getMessage());
            return [];
        }
    }
    
    public static function getProductById($product_id) {
        try {
            $product = wc_get_product($product_id);
            
            if (!$product || $product->get_status() !== 'publish') {
                return null;
            }
            
            return self::formatProductDetail($product);
            
        } catch (\Exception $e) {
            error_log('ProductRepository Error: ' . $e->getMessage());
            return null;
        }
    }
    
    public static function getCategories() {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        $formatted_categories = [];
        foreach ($categories as $category) {
            $formatted_categories[] = [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'image' => self::getCategoryImage($category->term_id)
            ];
        }
        
        return $formatted_categories;
    }
    
    public static function searchProducts($search_term, $limit = 10) {
        $args = [
            'status' => 'publish',
            'limit' => $limit,
            's' => $search_term,
            'return' => 'ids'
        ];
        
        try {
            $products = wc_get_products($args);
            
            $formatted_products = [];
            foreach ($products as $product_id) {
                $formatted_products[] = self::formatProductData($product_id);
            }
            
            return $formatted_products;
            
        } catch (\Exception $e) {
            error_log('ProductRepository Search Error: ' . $e->getMessage());
            return [];
        }
    }
    
    private static function formatProductData($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return null;
        }
        
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'image' => self::getProductImage($product),
            'rating' => $product->get_average_rating(),
            'review_count' => $product->get_review_count(),
            'stock_status' => $product->get_stock_status(),
            'in_stock' => $product->is_in_stock()
        ];
    }
    
    private static function formatProductDetail($product) {
        $product_data = self::formatProductData($product->get_id());
        
        if (!$product_data) {
            return null;
        }
        
        // Add detailed information
        $product_data['description'] = $product->get_description();
        $product_data['short_description'] = $product->get_short_description();
        $product_data['sku'] = $product->get_sku();
        $product_data['type'] = $product->get_type();
        $product_data['attributes'] = self::getProductAttributes($product);
        $product_data['gallery_images'] = self::getProductGallery($product);
        $product_data['categories'] = self::getProductCategories($product);
        $product_data['variations'] = self::getProductVariations($product);
        
        return $product_data;
    }
    
    private static function getProductImage($product) {
        $image_id = $product->get_image_id();
        return $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src();
    }
    
    private static function getProductGallery($product) {
        $gallery_images = [];
        $attachment_ids = $product->get_gallery_image_ids();
        
        foreach ($attachment_ids as $attachment_id) {
            $gallery_images[] = wp_get_attachment_image_url($attachment_id, 'medium');
        }
        
        return $gallery_images;
    }
    
    private static function getProductCategories($product) {
        $categories = [];
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
        
        foreach ($product_categories as $category) {
            $categories[] = [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug
            ];
        }
        
        return $categories;
    }
    
    private static function getProductAttributes($product) {
        $attributes = [];
        
        foreach ($product->get_attributes() as $attribute) {
            $attributes[] = [
                'name' => $attribute->get_name(),
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible()
            ];
        }
        
        return $attributes;
    }
    
    private static function getProductVariations($product) {
        if (!$product->is_type('variable')) {
            return [];
        }
        
        $variations = [];
        $available_variations = $product->get_available_variations();
        
        foreach ($available_variations as $variation) {
            $variations[] = [
                'id' => $variation['variation_id'],
                'price' => $variation['display_price'],
                'regular_price' => $variation['display_regular_price'],
                'attributes' => $variation['attributes'],
                'in_stock' => $variation['is_in_stock'],
                'image' => $variation['image'] ? $variation['image']['src'] : null
            ];
        }
        
        return $variations;
    }
    
    private static function getCategoryImage($category_id) {
        $image_id = get_term_meta($category_id, 'thumbnail_id', true);
        return $image_id ? wp_get_attachment_image_url($image_id, 'medium') : null;
    }
    
    private static function getTotalProductsCount($args) {
        $count_args = $args;
        $count_args['limit'] = -1;
        $count_args['return'] = 'ids';
        
        $products = wc_get_products($count_args);
        return is_array($products) ? count($products) : 0;
    }
}