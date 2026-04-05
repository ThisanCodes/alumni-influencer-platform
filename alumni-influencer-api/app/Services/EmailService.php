<?php

namespace App\Services;

use CodeIgniter\Config\Services;

class EmailService
{
    public function send(string $to, string $subject, string $message): bool
    {
        $email = Services::email();
        $email->clear(true);

        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);

        return $email->send();
    }

    public function sendVerificationEmail(string $to, string $token): bool
    {
        $verificationUrl = base_url('api/auth/verify-email?token=' . urlencode($token));

        $subject = 'Verify your email address';
        $message = "Welcome! Please verify your email by visiting this link:\n\n"
            . $verificationUrl
            . "\n\nThis link expires in 1 hour.";

        return $this->send($to, $subject, $message);
    }

    public function sendPasswordResetEmail(string $to, string $token): bool
    {
        $resetUrl = base_url('api/auth/reset-password?token=' . urlencode($token));

        $subject = 'Password Reset Request';
        $message = "You requested a password reset. Please click the link below to reset your password:\n\n"
            . $resetUrl
            . "\n\nThis link expires in 1 hour. If you did not request this, please ignore this email.";

        return $this->send($to, $subject, $message);
    }
}
