<?php

namespace App\Models;

class CertificationModel extends BaseProfileModel
{
    protected $table = 'certifications';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'name', 'issuing_organization', 'url', 'completion_date'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    protected $validationRules = [
        'name' => 'required|max_length[255]',
        'issuing_organization' => 'max_length[255]',
        'url' => 'permit_empty|valid_url_strict',
        'completion_date' => 'required|valid_date'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Certification name is required.',
            'max_length' => 'Certification name cannot exceed 255 characters.'
        ],
        'issuing_organization' => [
            'max_length' => 'Issuing organization cannot exceed 255 characters.'
        ],
        'url' => [
            'valid_url_strict' => 'URL must be a valid URL.'
        ],
        'completion_date' => [
            'required' => 'Completion date is required.',
            'valid_date' => 'Completion date must be a valid date.'
        ]
    ];
}