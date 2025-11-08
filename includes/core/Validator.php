<?php
namespace FoodFlow\Core;

class Validator {
    
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            foreach ($rulesArray as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[] = [
                        'field' => $field,
                        'message' => "The $field field is required",
                        'code' => 'VALIDATION_1001'
                    ];
                }
                
                if ($rule === 'email' && !empty($value) && !is_email($value)) {
                    $errors[] = [
                        'field' => $field,
                        'message' => "The $field must be a valid email address",
                        'code' => 'VALIDATION_1002'
                    ];
                }
                
                if (strpos($rule, 'min:') === 0 && !empty($value)) {
                    $min = (int) str_replace('min:', '', $rule);
                    if (strlen($value) < $min) {
                        $errors[] = [
                            'field' => $field,
                            'message' => "The $field must be at least $min characters",
                            'code' => 'VALIDATION_1003'
                        ];
                    }
                }
                
                if ($rule === 'unique:users' && !empty($value) && email_exists($value)) {
                    $errors[] = [
                        'field' => $field,
                        'message' => "The $field has already been taken",
                        'code' => 'VALIDATION_1004'
                    ];
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
}