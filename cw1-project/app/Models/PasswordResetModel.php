<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table = 'password_resets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'token',
        'expires_at',
        'created_at',
    ];

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function createToken(int $userId, string $expiresAt = '+1 hour'): string
    {
        $token = $this->generateToken();

        $this->revokeToken($userId);
        
        $this->insert([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime($expiresAt)),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        return $token;
    }

    public function revokeToken($userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
}
