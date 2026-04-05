<?php

namespace App\Models;

class DegreeModel extends BaseProfileModel
{
    protected $table = 'degrees';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'title', 'university', 'url', 'completion_date'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    protected $validationRules = [
        'title'=> 'required|max_length[255]',
        'university' => 'required|max_length[255]',
        'url'=> 'permit_empty|valid_url_strict|max_length[255]',
        'completion_date' => 'required|valid_date'
    ];

    protected $validationMessages = [
        'title' => [
            'required' => 'Degree title is required.',
            'max_length' => 'Degree title cannot exceed 255 characters.'
        ],
        'university' => [
            'required' => 'University name is required.',
            'max_length' => 'University name cannot exceed 255 characters.'
        ],
        'url' => [
            'valid_url_strict' => 'URL must be a valid URL.',
            'max_length' => 'URL cannot exceed 255 characters.'
        ],
        'completion_date' => [
            'required' => 'Completion date is required.',
            'valid_date' => 'Completion date must be a valid date.'
        ]
    ];
}