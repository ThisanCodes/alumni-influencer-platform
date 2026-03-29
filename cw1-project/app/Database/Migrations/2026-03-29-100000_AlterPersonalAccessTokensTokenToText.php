<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterPersonalAccessTokensTokenToText extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('personal_access_tokens', [
            'token' => [
                'type' => 'TEXT',
                'null' => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('personal_access_tokens', [
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
        ]);
    }
}
