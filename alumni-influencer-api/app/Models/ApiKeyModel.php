<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiKeyModel extends Model
{
    protected $table = 'api_keys';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'abilities',
        'expires_at',
        'last_used_at',
        'is_revoked',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[255]',
    ];

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public function generateKey(int $userId, string $name, ?string $expiresAt = null, array $abilities = ['*']): array
    {
        $rawKey = bin2hex(random_bytes(32));
        $prefix = substr($rawKey, 0, 8);

        $this->insert([
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => self::hashKey($rawKey),
            'key_prefix' => $prefix,
            'abilities' => json_encode($abilities),
            'expires_at' => $expiresAt ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null,
            'is_revoked' => false,
        ]);

        return [
            'id' => $this->getInsertID(),
            'name' => $name,
            'key' => $rawKey,
            'prefix' => $prefix,
            'abilities' => $abilities,
            'expires_at' => $expiresAt ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null,
        ];
    }

    public function getKeysForUser(int $userId): array
    {
        return $this->select('id, name, key_prefix, abilities, expires_at, last_used_at, is_revoked, created_at')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function getActiveKeysForUser(int $userId): array
    {
        return $this->select('id, name, key_prefix, abilities, expires_at, last_used_at, created_at')
            ->where('user_id', $userId)
            ->where('is_revoked', false)
            ->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function revokeKey(int $keyId, int $userId): bool
    {
        return (bool) $this->where('id', $keyId)
            ->where('user_id', $userId)
            ->set(['is_revoked' => true])
            ->update();
    }

    public function getKeyStats(int $keyId, int $userId): ?array
    {
        $key = $this->select('id, name, key_prefix, abilities, expires_at, last_used_at, is_revoked, created_at')
            ->where('id', $keyId)
            ->where('user_id', $userId)
            ->first();

        if (!$key) {
            return null;
        }

        $logModel = new ApiUsageLogModel();

        $totalRequests = $logModel->where('token_id', $keyId)
            ->where('user_id', $userId)
            ->countAllResults();

        $endpointBreakdown = $logModel->select('method, endpoint, COUNT(*) as hit_count')
            ->where('token_id', $keyId)
            ->where('user_id', $userId)
            ->groupBy('method, endpoint')
            ->orderBy('hit_count', 'DESC')
            ->findAll();

        $recentRequests = $logModel->where('token_id', $keyId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->findAll();

        $key['statistics'] = [
            'total_requests'     => $totalRequests,
            'endpoint_breakdown' => $endpointBreakdown,
            'recent_requests'    => $recentRequests,
        ];

        return $key;
    }

    public function touchLastUsed(int $keyId): void
    {
        $this->update($keyId, ['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public function findValidKey(string $rawKey): ?array
    {
        $record = $this->where('key_hash', self::hashKey($rawKey))
            ->where('is_revoked', false)
            ->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->first();

        return $record ?: null;
    }
}
