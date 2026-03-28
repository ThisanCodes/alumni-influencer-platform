<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['email', 'password'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['hashPassword'];

    protected $validationRules = [
        'email' => 'required|valid_email|university_email|is_unique[users.email]',
        'password' => 'required|min_length[8]',
    ];

    protected $validationMessages = [
        'email' => [
            'required' => 'Email is required.',
            'valid_email' => 'Please provide a valid email address.',
            'university_email' => 'Only university email addresses (iit.ac.lk) are allowed.',
            'is_unique' => 'This email is already registered.',
        ],
        'password' => [
            'required' => 'Password is required.',
            'min_length' => 'Password must be at least 8 characters long.',
        ],
    ];

    protected function hashPassword(array $data)
    {
       if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash(
                $data['data']['password'],
                PASSWORD_BCRYPT
            );
        }

        return $data;
    }   
}
