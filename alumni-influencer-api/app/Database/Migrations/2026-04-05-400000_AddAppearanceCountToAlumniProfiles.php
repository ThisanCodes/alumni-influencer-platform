<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAppearanceCountToAlumniProfiles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('alumni_profiles', [
            'appearance_count' => [
                'type'    => 'INT',
                'default' => 0
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('alumni_profiles', 'appearance_count');
    }
}
