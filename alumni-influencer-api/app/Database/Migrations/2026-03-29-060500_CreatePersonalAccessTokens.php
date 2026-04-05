<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePersonalAccessTokens extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'SERIAL',
            ],
            'user_id' => [
                'type' => 'INT',
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('token');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('personal_access_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('personal_access_tokens', true);
    }
}