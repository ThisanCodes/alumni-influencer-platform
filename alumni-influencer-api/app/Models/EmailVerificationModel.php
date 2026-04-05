<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailVerificationModel extends Model
{
    protected $table      = 'email_verifications';
    protected $primaryKey = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'token',
        'expires_at',
    ];

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function createToken(int $userId, string $expiresAt = '+1 hours'): string
    {
        $token = $this->generateToken();

        $this->where('user_id', $userId)->delete();

        $this->insert([
            'user_id' => $userId,
            'token' => self::hashToken($token),
            'expires_at' => date('Y-m-d H:i:s', strtotime($expiresAt)),
        ]);

        return $token;
    }


    public function findValidToken(string $plainToken): ?array
    {
        $record = $this->where('token', self::hashToken($plainToken))
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        return $record ?: null;
    }

    public function consumeToken(string $plainToken): ?array
    {
        $record = $this->findValidToken($plainToken);

        if (!$record) {
            return null;
        }

        $this->where('id', $record['id'])->delete();

        return $record;
    }

    public function revokeToken($userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
    
}
