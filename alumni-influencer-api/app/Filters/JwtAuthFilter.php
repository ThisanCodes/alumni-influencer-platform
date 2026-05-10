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
        $requirements = $this->parseRequirements($arguments);

        $apiKey = $request->getHeaderLine('X-API-Key');
        if (!empty($apiKey)) {
            return $this->authenticateWithApiKey($apiKey, $requirements);
        }

        if ($requirements['api_key_only']) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => 'This endpoint requires an X-API-Key header.',
                ]);
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

        if (!empty($requirements['abilities'])) {
            foreach ($requirements['abilities'] as $requiredAbility) {
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
                'role'  => $result['claims']['role'] ?? 'alumni',
            ]);
        }

        if (!$this->roleIsAllowed(AuthService::getUser()['role'] ?? null, $requirements['roles'])) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status' => false,
                    'message' => 'User role is not allowed to access this endpoint.',
                ]);
        }

        $tokenModel->touchLastUsed((int) $tokenRecord['id']);

        return null;
    }

    private function authenticateWithApiKey(string $apiKey, array $requirements)
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

        if (!empty($requirements['abilities'])) {
            $abilities = json_decode($keyRecord['abilities'] ?? '["*"]', true);
            foreach ($requirements['abilities'] as $requiredAbility) {
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
            'role'  => $user['role'] ?? 'alumni',
        ]);

        if (!$this->roleIsAllowed($user['role'] ?? null, $requirements['roles'])) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status' => false,
                    'message' => 'API key owner role is not allowed to access this endpoint.',
                ]);
        }

        $apiKeyModel->touchLastUsed((int) $keyRecord['id']);

        return null;
    }

    private function parseRequirements($arguments): array
    {
        $requirements = [
            'abilities' => [],
            'roles' => [],
            'api_key_only' => false,
        ];

        $arguments = array_values((array) ($arguments ?? []));

        for ($index = 0; $index < count($arguments); $index++) {
            $argument = $arguments[$index];

            if ($argument === 'api_key_only') {
                $requirements['api_key_only'] = true;
                continue;
            }

            if (str_starts_with($argument, 'role_')) {
                $requirements['roles'][] = substr($argument, 5);
                continue;
            }

            if (str_starts_with($argument, 'ability_')) {
                $requirements['abilities'][] = $this->decodeAbilityArgument(substr($argument, 8));
                continue;
            }

            if ($argument === 'read' && isset($arguments[$index + 1])) {
                $requirements['abilities'][] = 'read:' . $arguments[++$index];
                continue;
            }

            $requirements['abilities'][] = $argument;
        }

        return $requirements;
    }

    private function decodeAbilityArgument(string $argument): string
    {
        $parts = explode('_', $argument, 2);

        if (count($parts) !== 2) {
            return $argument;
        }

        return $parts[0] . ':' . $parts[1];
    }

    private function roleIsAllowed(?string $role, array $allowedRoles): bool
    {
        return empty($allowedRoles) || in_array($role, $allowedRoles, true);
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