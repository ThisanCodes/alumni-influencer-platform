<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastUsedAtToPersonalAccessTokens extends Migration
{
    public function up()
    {
        $this->forge->addColumn('personal_access_tokens', [
            'last_used_at' => [
                'type'  => 'TIMESTAMP',
                'null'  => true,
                'after' => 'expires_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('personal_access_tokens', 'last_used_at');
    }
}
