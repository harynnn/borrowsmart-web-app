<?php
require_once 'security.php';
require_once 'config.php';

class OTPHandler {
    private $pdo;
    private $otp_length = 6;
    private $otp_validity = 300; // 5 minutes in seconds

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createOTPTable();
    }

    private function createOTPTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS otp_codes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                code VARCHAR(6) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                is_used BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
    }

    public function generateOTP($user_id) {
        // Generate random 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing unused OTPs for this user
        $stmt = $this->pdo->prepare("
            DELETE FROM otp_codes 
            WHERE user_id = ? AND is_used = FALSE
        ");
        $stmt->execute([$user_id]);
        
        // Insert new OTP
        $stmt = $this->pdo->prepare("
            INSERT INTO otp_codes (user_id, code, expires_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
        ");
        $stmt->execute([$user_id, $code, $this->otp_validity]);
        
        return $code;
    }

    public function verifyOTP($user_id, $code) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM otp_codes 
            WHERE user_id = ? 
            AND code = ? 
            AND is_used = FALSE 
            AND expires_at > NOW()
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id, $code]);
        $otp = $stmt->fetch();

        if ($otp) {
            // Mark OTP as used
            $stmt = $this->pdo->prepare("
                UPDATE otp_codes 
                SET is_used = TRUE 
                WHERE id = ?
            ");
            $stmt->execute([$otp['id']]);
            return true;
        }
        return false;
    }

    public function sendOTPEmail($email, $code) {
        $subject = "Your BorrowSmart Login OTP";
        $message = "Your OTP code is: $code\n\n";
        $message .= "This code will expire in 5 minutes.\n";
        $message .= "If you didn't request this code, please ignore this email.";
        $headers = "From: otp.borrowsmart@gmail.com";

        return mail($email, $subject, $message, $headers);
    }
}
