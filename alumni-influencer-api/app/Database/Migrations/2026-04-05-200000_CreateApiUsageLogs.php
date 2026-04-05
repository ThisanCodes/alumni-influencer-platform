<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiUsageLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'SERIAL',
            ],
            'user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'token_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'method' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
            ],
            'endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'response_code' => [
                'type' => 'INT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('token_id');
        $this->forge->addKey('created_at');

        $this->forge->createTable('api_usage_logs');
    }

    public function down()
    {
        $this->forge->dropTable('api_usage_logs', true);
    }
}
