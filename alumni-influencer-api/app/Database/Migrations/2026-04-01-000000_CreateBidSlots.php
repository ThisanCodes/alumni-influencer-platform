<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBidSlots extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'is_active' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('bid_slots');
    }

    public function down()
    {
        $this->forge->dropTable('bid_slots');
    }
}
