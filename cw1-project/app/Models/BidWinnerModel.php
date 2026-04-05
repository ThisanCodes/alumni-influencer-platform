<?php

namespace App\Models;

use CodeIgniter\Model;

class BidWinnerModel extends Model
{
    protected $table = 'bid_winners';
    protected $primaryKey = 'id';
    protected $allowedFields = ['bid_id', 'slot_id', 'selected_at'];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    protected $validationRules = [
        'bid_id' => 'required|is_natural_no_zero',
        'slot_id' => 'required|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'bid_id' => [
            'required' => 'Bid is required.',
            'is_natural_no_zero' => 'Bid ID must be a positive integer.',
        ],
        'slot_id' => [
            'required' => 'Slot is required.',
            'is_natural_no_zero' => 'Slot ID must be a positive integer.',
        ],
    ];

    public function forSlot(int $slotId): ?array
    {
        return $this->where('slot_id', $slotId)->first();
    }

    public function isSlotAwarded(int $slotId): bool
    {
        return $this->where('slot_id', $slotId)->countAllResults() > 0;
    }
}
