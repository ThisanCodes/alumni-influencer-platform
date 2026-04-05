<?php

namespace App\Models;

class LicenceModel extends BaseProfileModel
{
    protected $table = 'licences';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'name', 'url', 'completion_date'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';
    
    protected $validationRules = [
        'name' => 'required|max_length[200]',
        'url' => 'permit_empty|valid_url_strict|max_length[255]',
        'completion_date' => 'required|valid_date',
    ];

    protected $validationMessages = [
        'name'  => [
            'required' => 'Licence name is required.',
            'max_length' => 'Licence name cannot exceed 200 characters.'
        ],
        'url' => [
            'valid_url_strict' => 'Please provide a valid licence URL.'
        ],
        'completion_date' => [
            'required' => 'Completion date is required.'
        ]
    ];
}