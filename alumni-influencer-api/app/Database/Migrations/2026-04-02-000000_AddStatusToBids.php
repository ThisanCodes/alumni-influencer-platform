<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToBids extends Migration
{
    public function up()
    {
        $this->forge->addColumn('bids', [
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'amount',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('bids', 'status');
    }
}
