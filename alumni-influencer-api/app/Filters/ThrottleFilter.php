<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $maxAttempts = 5;
        $decaySeconds = 60;

        $ip = $request->getIPAddress();
        $path = trim($request->getUri()->getPath(), '/');
        $key = 'throttle_' . md5($ip . '|' . $path);

        $cache = service('cache');
        $attempts = (int) $cache->get($key);

        if ($attempts >= $maxAttempts) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Too many requests. Please try again later.',
                ]);
        }

        $cache->save($key, $attempts + 1, $decaySeconds);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
