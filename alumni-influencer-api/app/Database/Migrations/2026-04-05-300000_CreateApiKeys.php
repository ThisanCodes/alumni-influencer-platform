<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeys extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'key_hash' => [
                'type' => 'TEXT',
            ],
            'key_prefix' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'abilities' => [
                'type'    => 'TEXT',
                'default' => '["*"]',
            ],
            'expires_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'last_used_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'is_revoked' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('key_hash');
        $this->forge->createTable('api_keys');
    }

    public function down()
    {
        $this->forge->dropTable('api_keys');
    }
}
