<?php

namespace App\Controllers\Api;

use App\Models\ApiKeyModel;
use App\Services\AuthService;
use App\Traits\SanitizesInput;
use CodeIgniter\RESTful\ResourceController;

class ApiKeyController extends ResourceController
{
    use SanitizesInput;

    protected ApiKeyModel $apiKeyModel;
    protected int $userId;

    public function __construct()
    {
        $this->apiKeyModel = new ApiKeyModel();
        $this->userId = (int) AuthService::getUserId();
    }

    public function generate()
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data) || empty($data)) {
            return $this->fail('Invalid or missing JSON payload.', 400);
        }

        $data = $this->sanitizeInput($data);

        if (empty($data['name'])) {
            return $this->failValidationErrors(['name' => 'API key name is required.']);
        }

        $abilities = $data['abilities'] ?? ['*'];
        if (!is_array($abilities)) {
            $abilities = ['*'];
        }

        $expiresAt = $data['expires_at'] ?? null;

        if ($expiresAt !== null) {
            $parsed = strtotime($expiresAt);
            if ($parsed === false || $parsed <= time()) {
                return $this->failValidationErrors(['expires_at' => 'Invalid or past expiry. Use a future strtotime-compatible string, e.g. "+30 days".']);
            }
        }

        $result = $this->apiKeyModel->generateKey(
            $this->userId,
            $data['name'],
            $expiresAt,
            $abilities
        );

        return $this->respondCreated([
            'status'  => true,
            'message' => 'API key generated successfully. Store the key securely — it will not be shown again.',
            'data'    => $result,
        ]);
    }

    public function index()
    {
        $keys = $this->apiKeyModel->getAllKeys();

        return $this->respond([
            'status' => true,
            'data'   => $keys,
        ]);
    }

    public function stats($id = null)
    {
        if (!$id) {
            return $this->fail('API key ID is required.', 400);
        }

        $stats = $this->apiKeyModel->getKeyStats((int) $id);

        if (!$stats) {
            return $this->failNotFound('API key not found.');
        }

        return $this->respond([
            'status' => true,
            'data'   => $stats,
        ]);
    }

    public function revoke($id = null)
    {
        if (!$id) {
            return $this->fail('API key ID is required.', 400);
        }

        $key = $this->apiKeyModel
            ->where('id', (int) $id)
            ->first();

        if (!$key) {
            return $this->failNotFound('API key not found.');
        }

        if ($this->isRevoked($key['is_revoked'] ?? false)) {
            return $this->fail('API key is already revoked.', 422);
        }

        $this->apiKeyModel->revokeKey((int) $id);

        return $this->respond([
            'status'  => true,
            'message' => 'API key revoked successfully.',
        ]);
    }

    private function isRevoked($value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
    }
}
