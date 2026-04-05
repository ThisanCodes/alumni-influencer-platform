<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsFeaturedToAlumniProfiles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('alumni_profiles', [
            'is_featured' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'profile_image',
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('alumni_profiles', 'is_featured');
    }
}
