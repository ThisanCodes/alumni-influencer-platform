<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonalAccessTokenModel extends Model
{
    protected $table = 'personal_access_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'token',
        'abilities',
        'expires_at',
        'last_used_at',
        'is_revoked',
        'created_at',
    ];

    protected $useTimestamps = false;

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function touchLastUsed(int $tokenId): void
    {
        $this->update($tokenId, ['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public function createToken(int $userId, string $token, string $expiresAt = '+24 hours', array $abilities = ['*']): string   
    {
        $this->insert([
            'user_id' => $userId,
            'token' => self::hashToken($token),
            'abilities' => json_encode($abilities),
            'expires_at' => date('Y-m-d H:i:s', strtotime($expiresAt)),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function findValidToken(string $plainToken): ?array
    {
        $record = $this->where('token', self::hashToken($plainToken))
            ->where('is_revoked', false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        return $record ?: null;
    }

    public function tokenCan(array $tokenRecord, string $ability): bool
    {
        $abilities = json_decode($tokenRecord['abilities'] ?? '["*"]', true);

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function revokeToken(string $token): bool
    {
        return (bool) $this->where('token', self::hashToken($token))
            ->where('is_revoked', false)
            ->set(['is_revoked' => true])
            ->update();
    }

    public function revokeAllForUser(int $userId): bool
    {
        return (bool) $this->where('user_id', $userId)
            ->where('is_revoked', false)
            ->set(['is_revoked' => true])
            ->update();
    }
}