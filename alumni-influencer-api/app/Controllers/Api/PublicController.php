<?php

namespace App\Controllers\Api;

use App\Models\AlumniProfileModel;
use App\Models\DegreeModel;
use App\Models\CertificationModel;
use App\Models\LicenceModel;
use App\Models\CourseModel;
use App\Models\EmploymentHistoryModel;
use CodeIgniter\RESTful\ResourceController;

class PublicController extends ResourceController
{
    public function featuredAlumnus()
    {
        $profileModel = new AlumniProfileModel();

        $featured = $profileModel
            ->where('is_featured', true)
            ->first();

        if (!$featured) {
            return $this->failNotFound('No featured alumnus for today.');
        }

        $userId = (int) $featured['user_id'];

        $featured['degrees'] = (new DegreeModel())
            ->where('user_id', $userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $featured['certifications'] = (new CertificationModel())
            ->where('user_id', $userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $featured['licences'] = (new LicenceModel())
            ->where('user_id', $userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $featured['courses'] = (new CourseModel())
            ->where('user_id', $userId)
            ->orderBy('completion_date', 'DESC')
            ->findAll();

        $featured['employment_history'] = (new EmploymentHistoryModel())
            ->where('user_id', $userId)
            ->orderBy('start_date', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => true,
            'data'   => $featured,
        ]);
    }
}
