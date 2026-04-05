<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\BidSlotModel;
use App\Models\BidModel;
use App\Models\BidWinnerModel;
use App\Models\AlumniProfileModel;
use App\Models\UserModel;
use App\Models\EventParticipationModel;
use App\Services\EmailService;

class SelectWinner extends BaseCommand
{
    protected $group = 'Bidding';
    protected $name = 'bids:select-winner';
    protected $description = 'Select the highest bidder as winner for today\'s slot (run at midnight)';

    public function run(array $params)
    {
        $today = date('Y-m-d');

        $slotModel = new BidSlotModel();
        $bidModel = new BidModel();
        $winnerModel = new BidWinnerModel();
        $profileModel = new AlumniProfileModel();
        $userModel = new UserModel();
        $emailService = new EmailService();
        $eventModel = new EventParticipationModel();

        $slot = $slotModel->where('date', $today)->where('is_active', 1)->first();

        if (!$slot) {
            CLI::error('No active slot found for today (' . $today . ').');
            return;
        }

        if ($winnerModel->isSlotAwarded((int) $slot['id'])) {
            CLI::error('A winner has already been selected for slot #' . $slot['id'] . '.');
            return;
        }

        $winningBid = $bidModel
            ->where('slot_id', $slot['id'])
            ->where('status', BidModel::STATUS['ACTIVE'])
            ->orderBy('amount', 'DESC')
            ->findAll();

        if (empty($winningBid)) {
            CLI::error('No active bids found for slot #' . $slot['id'] . '. Deactivating slot.');
            $slotModel->update($slot['id'], ['is_active' => 0]);
            return;
        }

        $selectedBid = null;
        foreach ($winningBid as $bid) {
            $bidderId = (int) $bid['user_id'];
            $bidderProfile = $profileModel->where('user_id', $bidderId)->first();
            $wins = (int) ($bidderProfile['appearance_count'] ?? 0);
            $limit = BidModel::MONTHLY_LIMIT;
            if ($eventModel->hasParticipatedThisMonth($bidderId)) {
                $limit += 1;
            }
            if ($wins < $limit) {
                $selectedBid = $bid;
                break;
            }
        }

        if (!$selectedBid) {
            CLI::error('All bidders have reached their monthly win limit for slot #' . $slot['id'] . '. Deactivating slot.');
            $slotModel->update($slot['id'], ['is_active' => 0]);
            return;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $winnerInserted = $winnerModel->insert([
            'bid_id' => $selectedBid['id'],
            'slot_id' => $slot['id'],
            'selected_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$winnerInserted) {
            $db->transRollback();
            CLI::error('Failed to insert winner record. Transaction rolled back.');
            return;
        }

        $profileModel->where('is_featured', true)->set(['is_featured' => false])->update();

        $profile = $profileModel->where('user_id', $selectedBid['user_id'])->first();
        if ($profile) {
            $profileModel->update($profile['id'], [
                'is_featured' => true,
                'appearance_count' => ((int) ($profile['appearance_count'] ?? 0)) + 1,
            ]);
        }

        $slotModel->update($slot['id'], ['is_active' => 0]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            CLI::error('Transaction failed during winner selection. All changes rolled back.');
            return;
        }

        $allBids = $bidModel
            ->where('slot_id', $slot['id'])
            ->where('status', BidModel::STATUS['ACTIVE'])
            ->findAll();

        $notifiedUsers = [];
        foreach ($allBids as $bid) {
            $userId = (int) $bid['user_id'];
            if (isset($notifiedUsers[$userId])) {
                continue;
            }
            $notifiedUsers[$userId] = true;

            $user = $userModel->find($userId);
            if (!$user) {
                continue;
            }

            $isWinner = ((int) $bid['id'] === (int) $selectedBid['id']);

            if ($isWinner) {
                $subject = 'Congratulations! You won the Alumni of the Day bid';
                $message = "Hi,\n\n"
                    . "You have won the Alumni of the Day slot for {$today} with a bid of {$selectedBid['amount']}!\n\n"
                    . "Your profile has been featured for today. Well done!\n\n"
                    . "— Alumni Influencer Platform";
            } else {
                $subject = 'Alumni of the Day bid result for ' . $today;
                $message = "Hi,\n\n"
                    . "Thank you for participating in today's bid slot ({$today}).\n\n"
                    . "Unfortunately, you were not selected as today's winner. The winning bid was {$selectedBid['amount']}.\n\n"
                    . "You can try again tomorrow!\n\n"
                    . "— Alumni Influencer Platform";
            }

            $sent = $emailService->send($user['email'], $subject, $message);

            if (!$sent) {
                CLI::write('Warning: Could not send email to ' . $user['email'], 'yellow');
            }
        }

        CLI::write('Winner selected: user #' . $selectedBid['user_id'] . ' (bid #' . $selectedBid['id'] . ', amount: ' . $selectedBid['amount'] . ') for slot "' . $slot['name'] . '".', 'green');
    }
}
