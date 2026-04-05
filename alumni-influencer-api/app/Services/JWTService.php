<?php

namespace App\Services;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class JWTService
{
	private string $secret;
	private string $algo;
	private int $ttl;
	private string $issuer;

	public function __construct()
	{
		$this->secret = (string) env('jwt.secret', '');
		$this->algo = (string) env('jwt.algo', 'HS256');
		$this->ttl = (int) env('jwt.ttl', 3600);
		$this->issuer = (string) env('jwt.issuer', base_url('/'));

		if ($this->secret === '') {
			throw new \RuntimeException('JWT secret is not configured. Set jwt.secret in .env');
		}
	}

	public function generateToken(array $claims = [], ?int $ttl = null): string
	{
		$issuedAt = time();
		$expiresAt = $issuedAt + ($ttl ?? $this->ttl);

		$payload = array_merge([
			'iss' => $this->issuer,
			'iat' => $issuedAt,
			'nbf' => $issuedAt,
			'exp' => $expiresAt,
		], $claims);

		return JWT::encode($payload, $this->secret, $this->algo);
	}

	public function validateToken(string $token): array
	{
		$decoded = JWT::decode($token, new Key($this->secret, $this->algo));

		return (array) $decoded;
	}

	public function getBearerToken(?string $authorizationHeader): ?string
	{
		if ($authorizationHeader === null || $authorizationHeader === '') {
			return null;
		}

		if (preg_match('/^Bearer\s+(.*)$/i', $authorizationHeader, $matches) !== 1) {
			return null;
		}

		return trim($matches[1]);
	}

	public function decodeFromHeader(?string $authorizationHeader): array
	{
		$token = $this->getBearerToken($authorizationHeader);

		if ($token === null) {
			return [
				'valid' => false,
				'message' => 'Missing or invalid Authorization header.',
				'claims' => null,
			];
		}

		try {
			$claims = $this->validateToken($token);

			return [
				'valid' => true,
				'message' => 'Token is valid.',
				'claims' => $claims,
			];
		} catch (ExpiredException $e) {
			return [
				'valid' => false,
				'message' => 'Token has expired.',
				'claims' => null,
			];
		} catch (Throwable $e) {
			return [
				'valid' => false,
				'message' => 'Invalid token.',
				'claims' => null,
			];
		}
	}
}
