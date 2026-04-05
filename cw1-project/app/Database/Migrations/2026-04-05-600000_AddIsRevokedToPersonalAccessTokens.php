<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsRevokedToPersonalAccessTokens extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE personal_access_tokens ADD COLUMN is_revoked BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(): void
    {
        $this->forge->dropColumn('personal_access_tokens', 'is_revoked');
    }
}
