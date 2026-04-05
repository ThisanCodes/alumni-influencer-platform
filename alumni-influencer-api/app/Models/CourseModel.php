<?php

namespace App\Models;

class CourseModel extends BaseProfileModel
{
    protected $table = 'courses';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'name', 'provider', 'url', 'completion_date'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    protected $validationRules = [
        'name' => 'required|max_length[255]',
        'provider' => 'required|max_length[255]',
        'url' => 'permit_empty|valid_url_strict|max_length[255]',
        'completion_date' => 'required|valid_date',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Course name is required.',
            'max_length' => 'Course name cannot exceed 255 characters.'
        ],
        'provider' => [
            'required' => 'Provider is required.',
            'max_length' => 'Provider cannot exceed 255 characters.'
        ],
        'url' => [
            'valid_url_strict' => 'Please provide a valid course URL.'
        ],
        'completion_date' => [
            'required' => 'Completion date is required.',
            'valid_date' => 'Completion date must be a valid date format.'
        ]
    ];
}