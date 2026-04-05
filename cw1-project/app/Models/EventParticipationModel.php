<?php

namespace App\Models;

use CodeIgniter\Model;

class EventParticipationModel extends BaseProfileModel
{
    protected $table = 'event_participations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'event_name', 'event_date'];
    protected $useTimestamps = true;
    protected $returnType = 'array';

    protected $validationRules = [
        'user_id' => 'required',
        'event_name' => 'required|string|max_length[255]',
        'event_date' => 'required|valid_date',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User is required.',
        ],
        'event_name' => [
            'required' => 'Event name is required.',
            'max_length' => 'Event name must not exceed 255 characters.',
        ],
        'event_date' => [
            'required' => 'Event date is required.',
            'valid_date' => 'Event date must be a valid date (Y-m-d).',
        ],
    ];

    public function hasParticipatedThisMonth(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('EXTRACT(MONTH FROM event_date) =', date('n'), false)
                    ->where('EXTRACT(YEAR FROM event_date) =', date('Y'), false)
                    ->countAllResults() > 0;
    }
}
