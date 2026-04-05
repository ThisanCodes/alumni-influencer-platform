<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbilitiesToPersonalAccessTokens extends Migration
{
    public function up()
    {
        $this->forge->addColumn('personal_access_tokens', [
            'abilities' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'token',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('personal_access_tokens', 'abilities');
    }
}
