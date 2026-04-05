<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIssuingOrganizationToCertifications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('certifications', [
            'issuing_organization' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'name',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('certifications', 'issuing_organization');
    }
}
