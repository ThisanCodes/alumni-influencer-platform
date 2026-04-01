<?php

namespace App\Controllers\Api;

use App\Models\CourseModel;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class CourseController extends ResourceController
{
    protected CourseModel $courseModel;
    protected $userId;

    public function __construct()
    {
        $this->courseModel = new CourseModel();
        $this->userId = AuthService::getUserId();
    }

    public function index()
    {
        $courses = $this->courseModel->forUser($this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $courses,
        ]);
    }

    public function show($id = null)
    {
        $course = $this->courseModel->findForUser($id, $this->userId);

        if (!$course) {
            return $this->failNotFound('Course not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $course,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        $data['user_id'] = $this->userId;

        if (!$this->courseModel->insert($data)) {
            return $this->failValidationErrors($this->courseModel->errors());
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Course created successfully.',
            'data' => $this->courseModel->find($this->courseModel->getInsertID()),
        ]);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? [];

        $course = $this->courseModel->findForUser($id, $this->userId);

        if (!$course) {
            return $this->failNotFound('Course not found.');
        }

        if (!$this->courseModel->update($id, $data)) {
            return $this->failValidationErrors($this->courseModel->errors());
        }

        return $this->respond([
            'status' => true,
            'message' => 'Course updated successfully.',
            'data' => $this->courseModel->find($id),
        ]);
    }

    public function delete($id = null)
    {
        $course = $this->courseModel->findForUser($id, $this->userId);

        if (!$course) {
            return $this->failNotFound('Course not found.');
        }

        if (!$this->courseModel->delete($id)) {
            return $this->failServerError('Could not delete course.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Course deleted successfully.',
        ]);
    }
}
