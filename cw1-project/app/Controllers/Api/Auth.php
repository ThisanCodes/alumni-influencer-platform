<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class Auth extends ResourceController
{
    public function register() 
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data) || empty($data)) {
            return $this->fail('Invalid or missing JSON payload.', 400);
        }

        $userModel = new UserModel();

        if (!$userModel->insert($data)) {
            if ($userModel->errors()) {
                return $this->failValidationErrors($userModel->errors());
            }
            return $this->failServerError('Registration failed. Please try again.');
        }

        $user = $userModel->find($userModel->getInsertID());

        return $this->respondCreated([
            'message' => 'User registered successfully.',
            'data' => [
                'id'         => $user['id'],
                'email'      => $user['email'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
            ],
        ]);
    }
}
