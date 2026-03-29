<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsVerifiedToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'is_verified' => [
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'is_verified');
    }
}
