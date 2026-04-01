<?php

namespace App\Filters;

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
        $result = $jwtService->decodeFromHeader($authorization);

        if (!$result['valid']) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'message' => $result['message'],
                ]);
        }

        if (isset($result['claims']) && is_array($result['claims'])) {
            AuthService::setUser([
                'id'    => $result['claims']['user_id'] ?? null,
                'email' => $result['claims']['email'] ?? null,
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}