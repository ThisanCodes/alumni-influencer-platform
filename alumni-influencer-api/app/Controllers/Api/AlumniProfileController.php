<?php

namespace App\Controllers\Api;

use App\Models\AlumniProfileModel;
use App\Models\CertificationModel;
use App\Models\CourseModel;
use App\Models\DegreeModel;
use App\Models\EmploymentHistoryModel;
use App\Models\EventParticipationModel;
use App\Models\LicenceModel;
use App\Services\AuthService;
use App\Traits\SanitizesInput;
use CodeIgniter\RESTful\ResourceController;

class AlumniProfileController extends ResourceController
{
    use SanitizesInput;
    protected AlumniProfileModel $profileModel;
    protected DegreeModel $degreeModel;
    protected CertificationModel $certificationModel;
    protected LicenceModel $licenceModel;
    protected CourseModel $courseModel;
    protected EmploymentHistoryModel $employmentModel;
    protected EventParticipationModel $eventParticipationModel;
    protected $userId;

    public function __construct()
    {
        $this->profileModel = new AlumniProfileModel();
        $this->degreeModel = new DegreeModel();
        $this->certificationModel = new CertificationModel();
        $this->licenceModel = new LicenceModel();
        $this->courseModel = new CourseModel();
        $this->employmentModel = new EmploymentHistoryModel();
        $this->eventParticipationModel = new EventParticipationModel();
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
        $data = $this->request->getJSON(true) ?? [];
        $data = $this->sanitizeInput($data);

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
        $data = $this->request->getJSON(true) ?? [];
        $data = $this->sanitizeInput($data);

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

        $uploadPath = FCPATH . 'uploads/profile_images';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        $newName = $file->getRandomName();
        if (!$file->move($uploadPath, $newName)) {
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
        $requestedUserId = (int) ($this->request->getGet('user_id') ?? 0);
        $targetUserId = $requestedUserId > 0 ? $requestedUserId : (int) $this->userId;

        $profile = $this->profileModel
            ->select('alumni_profiles.*, users.email')
            ->join('users', 'users.id = alumni_profiles.user_id', 'left')
            ->forUser($targetUserId)
            ->first();

        if (!$profile) {
            return $this->failNotFound('Profile not found.');
        }

        $profile['degrees'] = $this->degreeModel
            ->forUser($targetUserId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['certifications'] = $this->certificationModel
            ->where('user_id', $targetUserId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['licences'] = $this->licenceModel
            ->forUser($targetUserId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['courses'] = $this->courseModel
            ->forUser($targetUserId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $profile['employment_history'] = $this->employmentModel
            ->forUser($targetUserId)
            ->orderBy('start_date', 'DESC')
            ->findAll();

        $profile['event_participations'] = $this->eventParticipationModel
            ->forUser($targetUserId)
            ->orderBy('event_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data'   => $profile,
        ]);
    }

    public function allProfiles()
    {
        return $this->respond([
            'status' => true,
            'data' => $this->profileModel->findSummaries([
                'search' => $this->request->getGet('search'),
                'programme' => $this->request->getGet('programme'),
                'graduation_year' => $this->request->getGet('graduation_year'),
                'industry' => $this->request->getGet('industry'),
            ]),
        ]);
    }

    public function filterOptions()
    {
        return $this->respond([
            'status' => true,
            'data' => $this->profileModel->findFilterOptions(),
        ]);
    }
}
