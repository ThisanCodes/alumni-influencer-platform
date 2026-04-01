<?php

namespace App\Models;

class AlumniProfileModel extends BaseProfileModel
{
    protected $table = 'alumni_profiles';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'first_name', 'last_name', 'bio', 'linkedin_url', 'profile_image', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'array';

    protected $validationRules = [
        'first_name' => 'required|max_length[255]',
        'last_name' => 'required|max_length[255]',
        'bio' => 'permit_empty',
        'linkedin_url' => 'permit_empty|valid_url_strict',
        'profile_image' => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'first_name'=> [
            'required' => 'First name is required.',
            'max_length' => 'First name cannot exceed 255 characters.'
        ],
        'last_name' => [
            'required' => 'Last name is required.',
            'max_length' => 'Last name cannot exceed 255 characters.'
        ],
        'linkedin_url' => [
            'valid_url_strict' => 'LinkedIn URL must be a valid URL.'
        ],
        'profile_image' => [
            'mime_in' => 'Only JPEG and PNG images are allowed.',
            'is_image' => 'File must be a valid image.'
        ]
    ];
}