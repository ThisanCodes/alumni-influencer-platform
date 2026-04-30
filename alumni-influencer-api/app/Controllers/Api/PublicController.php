<?php

namespace App\Controllers\Api;

use App\Models\AlumniProfileModel;
use CodeIgniter\RESTful\ResourceController;

class PublicController extends ResourceController
{
    public function featuredAlumnus()
    {
        $featured = (new AlumniProfileModel())->findFeaturedProfile();

        if (!$featured) {
            return $this->failNotFound('No featured alumnus for today.');
        }

        return $this->respond([
            'status' => true,
            'data'   => $featured,
        ]);
    }
}
