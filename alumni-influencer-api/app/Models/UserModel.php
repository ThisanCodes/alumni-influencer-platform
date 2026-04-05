<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['email', 'password', 'is_verified'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected $validationRules = [
        'email' => 'required|valid_email|university_email|is_unique[users.email]',
        'password' => 'required|min_length[8]|regex_match[/[A-Z]/]|regex_match[/[a-z]/]|regex_match[/[0-9]/]|regex_match[/[^a-zA-Z0-9]/]',
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
            'regex_match' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ],
    ];

    protected function hashPassword(array $data)
    {
       if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash(
                $data['data']['password'],
                PASSWORD_BCRYPT,
                ['cost' => 12]
            );
        }

        return $data;
    }
}
