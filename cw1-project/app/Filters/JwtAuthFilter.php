<?php

namespace App\Filters;

use App\Models\ApiKeyModel;
use App\Models\ApiUsageLogModel;
use App\Models\PersonalAccessTokenModel;
use App\Models\UserModel;
use App\Services\AuthService;
use App\Services\JWTService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $apiKey = $request->getHeaderLine('X-API-Key');
        if (!empty($apiKey)) {
            return $this->authenticateWithApiKey($apiKey, $arguments);
        }

        try {
            $jwtService = new JWTService();
        } catch (RuntimeException $e) {
            return service('response')
                ->setStatusCode(500)
                ->setJSON([
                    'status' => false,
                    'message' => 'JWT configuration error.',
                ]);
        }

        $authorization = $request->getHeaderLine('Authorization');
        $rawToken = $jwtService->getBearerToken($authorization);

        if ($rawToken === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => 'Missing or invalid Authorization header. Provide a Bearer token or X-API-Key header.',
                ]);
        }

        $result = $jwtService->decodeFromHeader($authorization);

        if (!$result['valid']) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => $result['message'],
                ]);
        }

        $tokenModel = new PersonalAccessTokenModel();
        $tokenRecord = $tokenModel->findValidToken($rawToken);

        if (!$tokenRecord) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => 'Token has been revoked.',
                ]);
        }

        if (!empty($arguments)) {
            foreach ($arguments as $requiredAbility) {
                if (!$tokenModel->tokenCan($tokenRecord, $requiredAbility)) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'status' => false,
                            'message' => 'Token does not have the required ability: ' . $requiredAbility,
                        ]);
                }
            }
        }

        if (isset($result['claims']) && is_array($result['claims'])) {
            AuthService::setUser([
                'id'    => $result['claims']['user_id'] ?? null,
                'email' => $result['claims']['email'] ?? null,
            ]);
        }

        $tokenModel->touchLastUsed((int) $tokenRecord['id']);

        return null;
    }

    private function authenticateWithApiKey(string $apiKey, $arguments = null)
    {
        $apiKeyModel = new ApiKeyModel();
        $keyRecord = $apiKeyModel->findValidKey($apiKey);

        if (!$keyRecord) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => 'Invalid or revoked API key.',
                ]);
        }

        if (!empty($arguments)) {
            $abilities = json_decode($keyRecord['abilities'] ?? '["*"]', true);
            foreach ($arguments as $requiredAbility) {
                if (!in_array('*', $abilities, true) && !in_array($requiredAbility, $abilities, true)) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'status' => false,
                            'message' => 'API key does not have the required ability: ' . $requiredAbility,
                        ]);
                }
            }
        }

        $userModel = new UserModel();
        $user = $userModel->find($keyRecord['user_id']);

        if (!$user) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => 'API key owner not found.',
                ]);
        }

        AuthService::setUser([
            'id'    => (int) $user['id'],
            'email' => $user['email'],
        ]);

        $apiKeyModel->touchLastUsed((int) $keyRecord['id']);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $userId = AuthService::getUserId();

        if ($userId === null) {
            return null;
        }

        $tokenId = null;

        $apiKey = $request->getHeaderLine('X-API-Key');
        if (!empty($apiKey)) {
            $apiKeyModel = new ApiKeyModel();
            $keyRecord = $apiKeyModel->findValidKey($apiKey);
            $tokenId = $keyRecord ? (int) $keyRecord['id'] : null;
        } else {
            $jwtService = new JWTService();
            $rawToken = $jwtService->getBearerToken($request->getHeaderLine('Authorization'));

            if ($rawToken) {
                $tokenModel = new PersonalAccessTokenModel();
                $tokenRecord = $tokenModel->findValidToken($rawToken);
                $tokenId = $tokenRecord ? (int) $tokenRecord['id'] : null;
            }
        }

        $path = trim($request->getUri()->getPath(), '/');

        $logModel = new ApiUsageLogModel();
        $logModel->logRequest([
            'user_id' => $userId,
            'token_id' => $tokenId,
            'method' => $request->getMethod(),
            'endpoint' => $path,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'response_code' => $response->getStatusCode(),
        ]);

        return null;
    }
}