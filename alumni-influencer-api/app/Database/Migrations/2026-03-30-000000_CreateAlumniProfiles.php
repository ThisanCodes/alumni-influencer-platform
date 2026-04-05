<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAlumniProfiles extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'linkedin_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'profile_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('alumni_profiles');
    }

    public function down()
    {
        $this->forge->dropTable('alumni_profiles');
    }
}
