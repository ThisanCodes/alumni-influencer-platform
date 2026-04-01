<?php

namespace App\Controllers\Api;

use App\Models\AlumniProfileModel;
use App\Models\CertificationModel;
use App\Models\CourseModel;
use App\Models\DegreeModel;
use App\Models\EmploymentHistoryModel;
use App\Models\LicenceModel;
use App\Services\AuthService;
use CodeIgniter\RESTful\ResourceController;

class AlumniProfileController extends ResourceController
{
    protected AlumniProfileModel $profileModel;
    protected DegreeModel $degreeModel;
    protected CertificationModel $certificationModel;
    protected LicenceModel $licenceModel;
    protected CourseModel $courseModel;
    protected EmploymentHistoryModel $employmentModel;
    protected $userId;

    public function __construct()
    {
        $this->profileModel = new AlumniProfileModel();
        $this->degreeModel = new DegreeModel();
        $this->certificationModel = new CertificationModel();
        $this->licenceModel = new LicenceModel();
        $this->courseModel = new CourseModel();
        $this->employmentModel = new EmploymentHistoryModel();
        $this->userId = AuthService::getUserId();
    }

    public function show($id = null)
    {   
        $profile = $this->profileModel
            ->forUser($this->userId)
            ->first();

        if (!$profile) {
            return $this->failNotFound('Profile not found.');
        }

        return $this->respond([
            'status' => true,
            'data'   => $profile,
        ]);
    }

    public function create()
    {
        $data   = $this->request->getJSON(true) ?? [];

        $existing = $this->profileModel->forUser($this->userId)->first();
        if ($existing) {
            return $this->fail('Profile already exists for this user.', 400);
        }

        $data['user_id'] = $this->userId;

        if (!$this->profileModel->insert($data)) {
            return $this->failValidationErrors($this->profileModel->errors());
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Profile created successfully.',
            'data' => $this->profileModel->find($this->profileModel->getInsertID()),
        ]);
    }

    public function update($profileId = null)
    {
        $data   = $this->request->getJSON(true) ?? [];

        $profile = $this->profileModel->findForUser($profileId, $this->userId);

        if (!$profile) {
            return $this->failNotFound('Profile not found.');
        }

        if (!$this->profileModel->update($profileId, $data)) {
            return $this->failValidationErrors($this->profileModel->errors());
        }

        return $this->respond([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => $this->profileModel->find($profileId),
        ]);
    }

    public function uploadImage()
    {
        $profile = $this->profileModel->forUser($this->userId)->first();
        
        if (!$profile) {
            return $this->failNotFound('Profile not found.');
        }

        $file = $this->request->getFile('profile_image');
        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors(['profile_image' => 'Please upload a valid image.']);
        }

        $allowedMimes = ['image/jpeg', 'image/png'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->failValidationErrors(['profile_image' => 'Only JPEG and PNG images are allowed.']);
        }

        $newName = $file->getRandomName();
        if (!$file->move(WRITEPATH . 'uploads/profile_images', $newName)) {
            return $this->failServerError('Could not move uploaded file.');
        }

        $imagePath = 'uploads/profile_images/' . $newName;
        if (!$this->profileModel->update($profile['id'], ['profile_image' => $imagePath])) {
            return $this->failServerError('Could not update profile with image path.');
        }

        return $this->respond([
            'status' => true,
            'message' => 'Image uploaded successfully.',
            'data' => [
                'profile_image' => base_url($imagePath),
            ],
        ]);
    }

    public function fullProfile()
    {
        $profile = $this->profileModel
            ->forUser($this->userId)
            ->first();

        if (!$profile) {
            return $this->failNotFound('Profile not found.');
        }

        $profile['degrees'] = $this->degreeModel
            ->forUser($this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['certifications'] = $this->certificationModel
            ->where('user_id', $this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['licences'] = $this->licenceModel
            ->forUser($this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['courses'] = $this->courseModel
            ->forUser($this->userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['employment_history'] = $this->employmentModel
            ->forUser($this->userId)
            ->orderBy('start_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data'   => $profile,
        ]);
    }
}
