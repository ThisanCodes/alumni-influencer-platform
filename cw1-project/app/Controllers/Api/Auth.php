<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use App\Models\EmailVerificationModel;
use App\Models\PasswordResetModel;
use App\Models\PersonalAccessTokenModel;
use App\Services\EmailService;
use App\Services\JWTService;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

class Auth extends ResourceController
{
    protected UserModel $userModel;
    protected EmailVerificationModel $emailVerificationModel;
    protected PersonalAccessTokenModel $personalAccessTokenModel;
    protected PasswordResetModel $passwordResetModel;
    protected JWTService $jwtService;
    protected EmailService $emailService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->emailVerificationModel = new EmailVerificationModel();
        $this->personalAccessTokenModel = new PersonalAccessTokenModel();
        $this->passwordResetModel = new PasswordResetModel();
        $this->jwtService = new JWTService();
        $this->emailService = new EmailService();
    }

    public function register() 
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data) || empty($data)) {
            return $this->fail('Invalid or missing JSON payload.', 400);
        }

        if (!$this->userModel->validate($data)) {
            return $this->failValidationErrors($this->userModel->errors());
        }
        
        if (!$this->userModel->insert([
            'email' => $data['email'],
            'password' => $data['password'],
            'is_verified' => false,
        ])) {
            if ($this->userModel->errors()) {
                return $this->failValidationErrors($this->userModel->errors());
            }
            return $this->failServerError('Registration failed. Please try again.');
        }

        $userId = $this->userModel->getInsertID();

        $token = $this->emailVerificationModel->createToken($userId, '+1 hours');

        if (!$this->emailService->sendVerificationEmail($data['email'], $token)) {
            $this->userModel->delete($userId);
            $this->emailVerificationModel->revokeToken($userId);
            return $this->failServerError('Could not send verification email. Please try again.');
        }
        
        return $this->respondCreated([
            'status'  => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
        ]);
    }

    public function verifyEmail()
    {
        $token = $this->request->getGet('token');

        if (!$token) {
            return $this->fail('Verification token is required.', 400);
        }

        $verification = $this->emailVerificationModel
            ->where('token', $token)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$verification) {
            return $this->fail('Invalid or expired verification token.', 400);
        }

        $userId = $verification['user_id'];

        if (!$this->userModel->update($userId, ['is_verified' => true])) {
            return $this->failServerError('Could not verify email. Please try again.');
        }

        $this->emailVerificationModel->revokeToken($userId);

        return $this->respond([
            'status' => true,
            'message' => 'Email verified successfully. You can now log in.',
        ]);
    }

    public function login()
    {
        try {
            $this->jwtService = new JWTService();
        } catch (RuntimeException $e) {
            return $this->failServerError('JWT configuration error.');
        }

        $data = $this->request->getJSON(true);

        if (!is_array($data) || empty($data)) {
            return $this->fail('Invalid or missing JSON payload.', 400);
        }

        if (empty($data['email']) || empty($data['password'])) {
            return $this->failValidationErrors([
                'email' => 'Email is required.',
                'password' => 'Password is required.',
            ]);
        }

        $user = $this->userModel->where('email', $data['email'])->first();

        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $this->failUnauthorized('Invalid email or password.');
        }

        $verified = $user['is_verified'];
        if (!($verified === true || $verified === 't' || $verified === 1 || $verified === '1')) {
            return $this->fail('Please verify your email before logging in.', 403);
        }

        $token = $this->jwtService->generateToken([
            'sub' => (int) $user['id'],
            'email' => $user['email'],
        ]);

        $this->personalAccessTokenModel->revokeAllForUser($user['id']);
        $this->personalAccessTokenModel->createToken((int) $user['id'], $token, '+24 hours');

        return $this->respond([
            'status' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => [
                    'id' => (int) $user['id'],
                    'email' => $user['email'],
                ],
                'tokens' => [
                    'access_token' => $token,
                    'expires_in' => 3600,
                ],
            ]
        ]);
    }

    public function logout()
    {
        try {
            $this->jwtService = new JWTService();
        } catch (RuntimeException $e) {
            return $this->failServerError('JWT configuration error.');
        }

        $authorization = $this->request->getHeaderLine('Authorization');
        $token = $this->jwtService->getBearerToken($authorization);

        if ($token === null) {
            return $this->fail('Missing or invalid Authorization header.', 401);
        }

        $revoked = $this->personalAccessTokenModel->revokeToken($token);

        if (!$revoked) {
            return $this->failNotFound('Token not found or already revoked.');
        }

        return $this->respond([
            'status' => true,
            'token' => $token,
            'message' => 'Logout successful.',
        ]);
    }

    public function forgotPassword()
    {
        $data = $this->request->getJSON(true);
        $email = $data['email'] ?? null;

        if (empty($email)) {
            return $this->fail('Email is required.', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Invalid email format.', 400);
        }

        $genericResponse = [
            'status' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
        ];

        $user = $this->userModel->where('email', $email)->first();

        if (!$user) {
            return $this->respond($genericResponse);
        }

        try {
            $token = $this->passwordResetModel->createToken((int) $user['id'], '+1 hours');
            $this->emailService->sendPasswordResetEmail($email, $token);
        } catch (\Throwable $e) {
            log_message('error', 'Password reset email error: ' . $e->getMessage());
        }

        return $this->respond($genericResponse);
    }

    public function resetPassword()
    {
        $data = $this->request->getJSON(true);

        $token = $data['token'] ?? null;
        $newPassword = $data['new_password'] ?? null;

        if (!$token || !$newPassword) {
            return $this->fail('Token and new password are required.', 400);
        }

        if (strlen($newPassword) < 8) {
            return $this->fail('New password must be at least 8 characters long.', 400);
        }

        $reset = $this->passwordResetModel
            ->where('token', $token)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$reset) {
            return $this->fail('Invalid or expired password reset token.', 400);
        }

        $userId = $reset['user_id'];

        if (!$this->userModel->update($userId, ['password' => password_hash($newPassword, PASSWORD_DEFAULT)])) {
            return $this->failServerError('Could not reset password. Please try again.');
        }

        $this->passwordResetModel->revokeToken($userId);

        return $this->respond([
            'status' => true,
            'message' => 'Password reset successful. You can now log in with your new password.',
        ]);
    }
}
