<?php

namespace App\Models;

use CodeIgniter\Model;

class BidSlotModel extends Model
{
    protected $table = 'bid_slots';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'date', 'is_active'];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    protected $validationRules = [
        'name' => 'required|max_length[255]',
        'date' => 'required|valid_date',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Slot name is required.',
            'max_length' => 'Slot name cannot exceed 255 characters.',
        ],
        'date' => [
            'required' => 'Slot date is required.',
            'valid_date' => 'Slot date must be a valid date.',
        ],
    ];

    public function getActiveSlot()
    {
        return $this->where('is_active', 1)->first();
    }
}
