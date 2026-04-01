<?php

namespace App\Controllers\Api;

use App\Models\LicenceModel;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class LicenceController extends ResourceController
{
    protected LicenceModel $licenceModel;
    protected $userId;

    public function __construct()
    {
        $this->licenceModel = new LicenceModel();
        $this->userId = AuthService::getUserId();
    }

    public function index()
    {
        $licences = $this->licenceModel->forUser($this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $licences,
        ]);
    }

    public function show($id = null)
    {
        $licence = $this->licenceModel->findForUser($id, $this->userId);

        if (!$licence) {
            return $this->failNotFound('Licence not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $licence,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        $data['user_id'] = $this->userId;

        if (!$this->licenceModel->insert($data)) {
            return $this->failValidationErrors($this->licenceModel->errors());
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Licence created successfully.',
            'data' => $this->licenceModel->find($this->licenceModel->getInsertID()),
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];

        $licence = $this->licenceModel->findForUser($id, $this->userId);

        if (!$licence) {
            return $this->failNotFound('Licence not found.');
        }
        
        if (!$this->licenceModel->update($id, $data)) {
            return $this->failValidationErrors($this->licenceModel->errors());
        }

        return $this->respond([
            'status' => true,
            'message' => 'Licence updated successfully.',
            'data' => $this->licenceModel->find($id),
        ]);
    }

    public function delete($id = null)
    {
        $licence = $this->licenceModel->findForUser($id, $this->userId);

        if (!$licence) {
            return $this->failNotFound('Licence not found.');
        }

        if (!$this->licenceModel->delete($id)) {
            return $this->failServerError('Could not delete licence.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Licence deleted successfully.',
        ]);
    }
}
