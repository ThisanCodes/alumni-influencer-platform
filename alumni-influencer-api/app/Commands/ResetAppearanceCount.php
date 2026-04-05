<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\AlumniProfileModel;

class ResetAppearanceCount extends BaseCommand
{
    protected $group = 'Bidding';
    protected $name = 'bids:reset-appearance-count';
    protected $description = 'Reset appearance count for all alumni profiles (run monthly on the 1st)';

    public function run(array $params)
    {
        $profileModel = new AlumniProfileModel();
        $db = \Config\Database::connect();

        $db->transStart();

        $updated = $profileModel->where('appearance_count >', 0)
            ->set(['appearance_count' => 0])
            ->update();

        $db->transComplete();

        if ($db->transStatus() === false) {
            CLI::error('Transaction failed. Appearance counts were not reset.');
            return;
        }

        if ($updated) {
            CLI::write('Appearance counts have been reset for all alumni profiles.', 'green');
        } else {
            CLI::write('No profiles needed resetting.', 'yellow');
        }
    }
}
