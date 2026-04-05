<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeIsFeaturedToBoolean extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE alumni_profiles ALTER COLUMN is_featured DROP DEFAULT');
        $this->db->query('ALTER TABLE alumni_profiles ALTER COLUMN is_featured TYPE BOOLEAN USING is_featured::int::boolean');
        $this->db->query('ALTER TABLE alumni_profiles ALTER COLUMN is_featured SET DEFAULT FALSE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE alumni_profiles ALTER COLUMN is_featured DROP DEFAULT');
        $this->db->query('ALTER TABLE alumni_profiles ALTER COLUMN is_featured TYPE SMALLINT USING is_featured::int');
        $this->db->query('ALTER TABLE alumni_profiles ALTER COLUMN is_featured SET DEFAULT 0');
    }
}
