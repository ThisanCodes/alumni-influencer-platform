<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use App\Models\EmailVerificationModel;
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
    protected EmailService $emailService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->emailVerificationModel = new EmailVerificationModel();
        $this->personalAccessTokenModel = new PersonalAccessTokenModel();
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
            $jwtService = new JWTService();
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

        $token = $jwtService->generateToken([
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
            $jwtService = new JWTService();
        } catch (RuntimeException $e) {
            return $this->failServerError('JWT configuration error.');
        }

        $authorization = $this->request->getHeaderLine('Authorization');
        $token = $jwtService->getBearerToken($authorization);

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
}
