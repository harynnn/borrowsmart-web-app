<?php
// Session configuration
function configureSecureSession() {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    
    // Set session timeout to 5 minutes (300 seconds)
    ini_set('session.gc_maxlifetime', 300);
    session_set_cookie_params(300);
}

// Check session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        $inactive = 300; // 5 minutes in seconds
        if (time() - $_SESSION['LAST_ACTIVITY'] > $inactive) {
            // Session expired
            session_unset();
            session_destroy();
            header("Location: /login.php?timeout=1");
            exit();
        }
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // Update last activity timestamp
}

// Input validation
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Encryption functions
function encrypt($data, $key) {
    $cipher = "aes-256-cbc";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt($data, $key) {
    $cipher = "aes-256-cbc";
    $data = base64_decode($data);
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $ivlen);
    $encrypted = substr($data, $ivlen);
    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
}

// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
    return true;
}

// XSS Prevention function
function escapeHTML($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// SQL Injection Prevention
function prepareSQL($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Password Hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 2048,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Rate Limiting
function checkRateLimit($ip, $limit = 5, $minutes = 5) {
    $file = sys_get_temp_dir() . '/rate_limits.json';
    $current_time = time();
    
    // Load existing rate limits
    $rates = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    // Clean up old entries
    foreach ($rates as $stored_ip => $data) {
        if ($current_time - $data['timestamp'] > $minutes * 60) {
            unset($rates[$stored_ip]);
        }
    }
    
    // Check current IP
    if (isset($rates[$ip])) {
        if ($rates[$ip]['count'] >= $limit && 
            $current_time - $rates[$ip]['timestamp'] < $minutes * 60) {
            return false; // Rate limit exceeded
        }
        $rates[$ip]['count']++;
        $rates[$ip]['timestamp'] = $current_time;
    } else {
        $rates[$ip] = [
            'count' => 1,
            'timestamp' => $current_time
        ];
    }
    
    // Save updated rate limits
    file_put_contents($file, json_encode($rates));
    return true;
}
