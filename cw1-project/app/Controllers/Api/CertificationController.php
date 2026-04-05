<?php

namespace App\Controllers\Api;

use App\Models\CertificationModel;
use App\Services\AuthService;
use App\Traits\SanitizesInput;
use CodeIgniter\RESTful\ResourceController;

class CertificationController extends ResourceController
{
    use SanitizesInput;
    protected CertificationModel $certificationModel;
    protected $userId;

    public function __construct()
    {
        $this->certificationModel = new CertificationModel();
        $this->userId = AuthService::getUserId();
    }

    public function index()
    {
        $certifications = $this->certificationModel->forUser($this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $certifications,
        ]);
    }

    public function show($id = null)
    {
        $certification = $this->certificationModel->findForUser($id, $this->userId);

        if (!$certification) {
            return $this->failNotFound('Certification not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $certification,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        $data = $this->sanitizeInput($data);

        $data['user_id'] = $this->userId;

        if (!$this->certificationModel->insert($data)) {
            return $this->failValidationErrors($this->certificationModel->errors());
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Certification created successfully.',
            'data' => $this->certificationModel->find($this->certificationModel->getInsertID()),
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];
        $data = $this->sanitizeInput($data);

        $certification = $this->certificationModel->findForUser($id, $this->userId);

        if (!$certification) {
            return $this->failNotFound('Certification not found.');
        }

        if (!$this->certificationModel->update($id, $data)) {
            return $this->failValidationErrors($this->certificationModel->errors());
        }

        return $this->respond([
            'status' => true,
            'message' => 'Certification updated successfully.',
            'data' => $this->certificationModel->find($id),
        ]);
    }

    public function delete($id = null)
    {
        $certification = $this->certificationModel->findForUser($id, $this->userId);

        if (!$certification) {
            return $this->failNotFound('Certification not found.');
        }

        if (!$this->certificationModel->delete($id)) {
            return $this->failServerError('Could not delete certification.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Certification deleted successfully.',
        ]);
    }
}
