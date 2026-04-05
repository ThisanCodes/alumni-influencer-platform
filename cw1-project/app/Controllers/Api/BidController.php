<?php

namespace App\Controllers\Api;

use App\Models\BidModel;
use App\Models\BidSlotModel;
use App\Models\BidWinnerModel;
use App\Models\EventParticipationModel;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class BidController extends ResourceController
{
    protected BidModel $bidModel;
    protected BidSlotModel $slotModel;
    protected BidWinnerModel $winnerModel;
    protected EventParticipationModel $eventModel;
    protected $userId;

    public function __construct()
    {
        $this->bidModel = new BidModel();
        $this->slotModel = new BidSlotModel();
        $this->winnerModel = new BidWinnerModel();
        $this->eventModel = new EventParticipationModel();
        $this->userId = AuthService::getUserId();
    }

    private function getMonthlyLimit(): int
    {
        $base = BidModel::MONTHLY_LIMIT;
        if ($this->eventModel->hasParticipatedThisMonth($this->userId)) {
            return $base + 1;
        }

        return $base;
    }

    public function placeBid()
    {
        $data = $this->request->getJSON(true) ?? [];

        $activeSlot = $this->slotModel->getActiveSlot();
        if (!$activeSlot) {
            return $this->fail('No active bidding slot available.', 400);
        }

        if ($this->bidModel->hasActiveBid($this->userId, $activeSlot['id'])) {
            return $this->fail('You already have an active bid for this slot.', 422);
        }

        $data['user_id'] = $this->userId;
        $data['slot_id'] = $activeSlot['id'];
        $data['status'] = BidModel::STATUS['ACTIVE'];

        if (!$this->bidModel->insert($data)) {
            return $this->failValidationErrors($this->bidModel->errors(), 400);
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Bid placed successfully.',
            'data' => [
                'bid_id' => $this->bidModel->getInsertID(),
                'slot_id' => $activeSlot['id'],
            ],
        ]);
    }

    public function updateBid($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];

        $bid = $this->bidModel->find($id);
        if (!$bid || $bid['user_id'] != $this->userId) {
            return $this->failNotFound('Bid not found or access denied.');
        }

        if ((int) $bid['status'] !== BidModel::STATUS['ACTIVE']) {
            return $this->fail('Only active bids can be updated.', 422);
        }

        $activeSlot = $this->slotModel->getActiveSlot();
        if (!$activeSlot || (int) $bid['slot_id'] !== (int) $activeSlot['id']) {
            return $this->fail('Bid cannot be updated because its slot is no longer active.', 422);
        }

        $newAmount = $data['amount'] ?? null;

        if ($newAmount === null) {
            return $this->fail('Amount is required.', 400);
        }

        if ((float) $newAmount <= (float) $bid['amount']) {
            return $this->fail('New bid amount must be higher than the current amount of ' . $bid['amount'] . '.', 422);
        }

        if (!$this->bidModel->update($id, ['amount' => $newAmount])) {
            return $this->failValidationErrors($this->bidModel->errors(), 400);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Bid updated successfully.',
            'data' => $this->bidModel->find($id),
        ]);
    }

    public function cancelBid($id = null)
    {
        $bid = $this->bidModel->find($id);
        if (!$bid || $bid['user_id'] != $this->userId) {
            return $this->failNotFound('Bid not found or access denied.');
        }

        if ((int) $bid['status'] === BidModel::STATUS['CANCELLED']) {
            return $this->fail('Bid is already cancelled.', 422);
        }

        $activeSlot = $this->slotModel->getActiveSlot();
        if (!$activeSlot || (int) $bid['slot_id'] !== (int) $activeSlot['id']) {
            return $this->fail('Bid cannot be cancelled because its slot is no longer active.', 422);
        }

        if (!$this->bidModel->update($id, ['status' => BidModel::STATUS['CANCELLED']])) {
            return $this->failValidationErrors($this->bidModel->errors(), 500);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Bid cancelled successfully.',
        ]);
    }

    public function bidHistory()
    {
        return $this->respond([
            'status' => true,
            'data' => $this->bidModel->historyForUser($this->userId),
        ]);
    }

    public function monthlyLimitStatus()
    {
        $wins = $this->winnerModel->monthlyWinCount($this->userId);
        $limit = $this->getMonthlyLimit();
        $hasEventBonus = $this->eventModel->hasParticipatedThisMonth($this->userId);
        $remaining = max(0, $limit - $wins);

        return $this->respond([
            'status' => true,
            'data' => [
                'wins_this_month' => $wins,
                'limit' => $limit,
                'remaining' => $remaining,
                'label' => $wins . '/' . $limit,
                'limit_reached' => $remaining === 0,
                'event_bonus_applied' => $hasEventBonus,
            ],
        ]);
    }
}
