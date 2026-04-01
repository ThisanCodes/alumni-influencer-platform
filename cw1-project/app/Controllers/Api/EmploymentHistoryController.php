<?php

namespace App\Controllers\Api;

use App\Models\EmploymentHistoryModel;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class EmploymentHistoryController extends ResourceController
{
    protected EmploymentHistoryModel $employmentHistoryModel;
    protected $userId;

    public function __construct()
    {
        $this->employmentHistoryModel = new EmploymentHistoryModel();
        $this->userId = AuthService::getUserId();
    }

    public function index()
    {
        $employmentHistory = $this->employmentHistoryModel
            ->forUser($this->userId)
            ->orderBy('start_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $employmentHistory,
        ]);
    }

    public function show($id = null)
    {
        $employment = $this->employmentHistoryModel->findForUser($id, $this->userId);

        if (!$employment) {
            return $this->failNotFound('Employment history not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $employment,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        $data['user_id'] = $this->userId;

        if (!$this->employmentHistoryModel->insert($data)) {
            return $this->failValidationErrors($this->employmentHistoryModel->errors());
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Employment history created successfully.',
            'data' => $this->employmentHistoryModel->find($this->employmentHistoryModel->getInsertID()),
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];

        $employment = $this->employmentHistoryModel->findForUser($id, $this->userId);

        if (!$employment) {
            return $this->failNotFound('Employment history not found.');
        }

        if (!$this->employmentHistoryModel->update($id, $data)) {
            return $this->failValidationErrors($this->employmentHistoryModel->errors());
        }

        return $this->respond([
            'status' => true,
            'message' => 'Employment history updated successfully.',
            'data' => $this->employmentHistoryModel->find($id),
        ]);
    }

    public function delete($id = null)
    {
        $employment = $this->employmentHistoryModel->findForUser($id, $this->userId);

        if (!$employment) {
            return $this->failNotFound('Employment history not found.');
        }

        if (!$this->employmentHistoryModel->delete($id)) {
            return $this->failServerError('Could not delete employment history.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Employment history deleted successfully.',
        ]);
    }
}
