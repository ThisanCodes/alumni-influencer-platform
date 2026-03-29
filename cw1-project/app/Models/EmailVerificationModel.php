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

    public function createToken(int $userId, string $expiresAt = '+1 hours'): string
    {
        $token = $this->generateToken();

        $this->where('user_id', $userId)->delete();

        $this->insert([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime($expiresAt)),
        ]);

        return $token;
    }

    public function revokeToken($userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
    
}
