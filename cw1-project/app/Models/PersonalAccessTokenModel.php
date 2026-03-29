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
        'expires_at',
        'created_at',
    ];

    protected $useTimestamps = false;

    public function createToken(int $userId, string $token, string $expiresAt = '+24 hours'): string   
    {
        $this->insert([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime($expiresAt)),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function revokeToken(string $token): bool
    {
        return (bool) $this->where('token', $token)->delete();
    }

    public function revokeAllForUser(int $userId): bool
    {
        return (bool) $this->where('user_id', $userId)->delete();
    }
}