<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class Auth extends ResourceController
{
    public function register() 
    {
        $data = $this->request->getJSON(true);

        $userModel = new UserModel();

        if (!$userModel->validate($data)) {
            return $this->failValidationErrors($userModel->errors());
        }

        if (!$userModel->insert($data)) {
            return $this->failServerError('Registration failed. Please try again.');
        }

        $user = $userModel->find($userModel->getInsertID());

        return $this->respondCreated([
            'message' => 'User registered successfully.',
            'data'    => $user,
        ]);
    }
}
