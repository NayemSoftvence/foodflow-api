<?php
namespace FoodFlow\Utils;

class Constants {
    // API Response Codes
    const SUCCESS = 200;
    const CREATED = 201;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const VALIDATION_ERROR = 422;
    const SERVER_ERROR = 500;
    
    // JWT Configuration
    const JWT_EXPIRY_HOURS = 24;
    const JWT_ALGORITHM = 'HS256'; // ADD THIS LINE
    
    // OTP Configuration
    const OTP_LENGTH = 6;
    const OTP_EXPIRY_MINUTES = 10;
    
    // User Roles
    const ROLE_CUSTOMER = 'customer';
}