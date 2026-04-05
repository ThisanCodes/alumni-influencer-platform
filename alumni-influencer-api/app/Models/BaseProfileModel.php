<?php

namespace App\Models;

use CodeIgniter\Model;


abstract class BaseProfileModel extends Model
{
    public function forUser(int $userId)
    {
        return $this->where('user_id', $userId);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->where('id', $id)
                    ->where('user_id', $userId)
                    ->first();
    }
}
