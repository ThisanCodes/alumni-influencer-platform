<?php

namespace App\Models;

use CodeIgniter\Model;

class BidModel extends Model
{
    protected $table = 'bids';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'slot_id', 'amount', 'status'];
    protected $useTimestamps = true;
    protected $returnType = 'array';

    public const STATUS = ['ACTIVE' => 1, 'CANCELLED' => 2];

    public const MONTHLY_LIMIT = 3;

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'slot_id' => 'required|is_natural_no_zero',
        'amount' => 'required|decimal|greater_than[0]',
        'status' => 'permit_empty|in_list[1,2]',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User is required.',
            'is_natural_no_zero'  => 'User ID must be a positive integer.',
        ],
        'slot_id' => [
            'required' => 'Slot is required.',
            'is_natural_no_zero' => 'Slot ID must be a positive integer.',
        ],
        'amount' => [
            'required' => 'Bid amount is required.',
            'decimal' => 'Bid amount must be a valid decimal number.',
            'greater_than' => 'Bid amount must be greater than 0.',
        ],
        'status' => [
            'in_list' => 'Status must be 1 (active) or 2 (cancelled).',
        ],
    ];

    public function hasActiveBid(int $userId, int $slotId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('slot_id', $slotId)
                    ->where('status', self::STATUS['ACTIVE'])
                    ->countAllResults() > 0;
    }

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function historyForUser(int $userId): array
    {
        $bids = $this->db->table('bids')
            ->select('bids.*, bs.name AS slot_name, bs.date AS slot_date, bs.is_active AS slot_is_active, bw.bid_id AS winner_bid_id')
            ->join('bid_slots bs', 'bs.id = bids.slot_id')
            ->join('bid_winners bw', 'bw.slot_id = bids.slot_id', 'left')
            ->where('bids.user_id', $userId)
            ->get()
            ->getResultArray();

        foreach ($bids as &$bid) {
            if ((int) $bid['status'] === self::STATUS['CANCELLED']) {
                $bid['outcome'] = 'CANCELLED';
            } elseif ((int) $bid['slot_is_active'] === 1) {
                $bid['outcome'] = 'PENDING';
            } elseif (!empty($bid['winner_bid_id']) && (int) $bid['winner_bid_id'] === (int) $bid['id']) {
                $bid['outcome'] = 'WIN';
            } else {
                $bid['outcome'] = 'LOSE';
            }
            unset($bid['winner_bid_id']);
        }

        return $bids;
    }

    public function forSlot(int $slotId): array
    {
        return $this->where('slot_id', $slotId)->findAll();
    }

    public function highestBidForSlot(int $slotId): ?array
    {
        return $this->where('slot_id', $slotId)
                    ->orderBy('amount', 'DESC')
                    ->first();
    }

    public function monthlyBidCount(int $userId): int
    {
        return $this->where('user_id', $userId)
                    ->where('status', self::STATUS['ACTIVE'])
                    ->where('EXTRACT(MONTH FROM created_at) =', date('n'), false)
                    ->where('EXTRACT(YEAR FROM created_at) =', date('Y'), false)
                    ->countAllResults();
    }
}
