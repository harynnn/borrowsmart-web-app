<?php
// Encryption key - in production, this should be stored securely and loaded from environment variables
define('ENCRYPTION_KEY', '5c1b35c2e8a188f138c0c443d094c762'); // Example key, generate a secure one in production

// Database configuration - in production, these should be loaded from environment variables
define('DB_HOST', 'localhost');
define('DB_NAME', 'borrowsmart');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security configuration
define('SESSION_TIMEOUT', 300); // 5 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum login attempts before temporary lockout
define('LOCKOUT_TIME', 300); // 5 minutes lockout after max login attempts
define('PASSWORD_MIN_LENGTH', 8); // Minimum password length
define('REQUIRE_SPECIAL_CHARS', true); // Require special characters in passwords
define('CSRF_TOKEN_EXPIRY', 3600); // CSRF token expiry in seconds (1 hour)

// Cookie settings
define('COOKIE_SECURE', true); // Only transmit cookies over HTTPS
define('COOKIE_HTTPONLY', true); // Make cookies accessible only through HTTP(S)
define('COOKIE_SAMESITE', 'Strict'); // Strict SameSite cookie policy
