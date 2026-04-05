<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\BidSlotModel;

class CreateSlot extends BaseCommand
{
    protected $group = 'Bidding';
    protected $name = 'bids:create-slot';
    protected $description = 'Create a new bid slot';

    public function run(array $params)
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $bidSlotModel = new BidSlotModel();

        $exists = $bidSlotModel->where('date', $tomorrow)->first();
        if ($exists) {
            CLI::error('A slot for tomorrow already exists.');
            return;
        }

        $bidSlotModel->where('is_active', 1)->set(['is_active' => 0])->update();

        $bidSlotModel->insert([
            'name' => 'Alumni of the Day - ' . $tomorrow,
            'date' => $tomorrow,
            'is_active' => 1,
        ]);

        CLI::write('Bid slot for tomorrow created successfully.');
    }
}
