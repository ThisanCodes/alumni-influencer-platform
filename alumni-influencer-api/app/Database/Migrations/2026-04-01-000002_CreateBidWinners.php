<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBidWinners extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'bid_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'slot_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'selected_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('bid_id', 'bids', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('slot_id', 'bid_slots', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bid_winners');
    }

    public function down()
    {
        $this->forge->dropTable('bid_winners');
    }
}
