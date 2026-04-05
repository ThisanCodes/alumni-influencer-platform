<?php

namespace App\Models;

class EmploymentHistoryModel extends BaseProfileModel
{
    protected $table = 'employment_history';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'company', 'role', 'start_date', 'end_date', 'is_current'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

     protected $validationRules = [
        'company' => 'required|max_length[255]',
        'role' => 'required|max_length[255]',
        'start_date' => 'required|valid_date',
        'end_date' => 'permit_empty|valid_date',
        'is_current' => 'permit_empty',
    ];

    protected $validationMessages = [
        'company' => [
            'required' => 'Company name is required.',
            'max_length' => 'Company name cannot exceed 255 characters.'
        ],
        'role' => [
            'required' => 'Role is required.',
            'max_length' => 'Role cannot exceed 255 characters.'
        ],
        'start_date' => [
            'required' => 'Start date is required.',
            'valid_date' => 'Start date must be a valid date format.'
        ],
        'end_date' => [
            'valid_date' => 'End date must be a valid date format.'
        ]
    ];
}