<?php

namespace App\Controllers\Api;

use App\Models\DegreeModel;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class DegreeController extends ResourceController
{
    protected DegreeModel $degreeModel;
    protected $userId;

    public function __construct()
    {
        $this->degreeModel = new DegreeModel();
        $this->userId = AuthService::getUserId();
    }

    public function index()
    {
        $degrees = $this->degreeModel
            ->forUser($this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $degrees,
        ]);
    }

    public function show($id = null)
    {
        $degree = $this->degreeModel->findForUser($id, $this->userId);

        if (!$degree) {
            return $this->failNotFound('Degree not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $degree,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        $data['user_id'] = $this->userId;

        if (!$this->degreeModel->insert($data)) {
            return $this->failValidationErrors($this->degreeModel->errors());
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Degree created successfully.',
            'data' => $this->degreeModel->find($this->degreeModel->getInsertID()),
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];

        $degree = $this->degreeModel->findForUser($id, $this->userId);

        if (!$degree) {
            return $this->failNotFound('Degree not found.');
        }

        if (!$this->degreeModel->update($id, $data)) {
            return $this->failValidationErrors($this->degreeModel->errors());
        }

        return $this->respond([
            'status' => true,
            'message' => 'Degree updated successfully.',
            'data' => $this->degreeModel->find($id),
        ]);
    }

    public function delete($id = null)
    {
        $degree = $this->degreeModel->findForUser($id, $this->userId);

        if (!$degree) {
            return $this->failNotFound('Degree not found.');
        }

        if (!$this->degreeModel->delete($id)) {
            return $this->failServerError('Could not delete degree.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Degree deleted successfully.',
        ]);
    }
}
