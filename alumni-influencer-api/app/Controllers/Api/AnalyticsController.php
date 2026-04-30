<?php

namespace App\Controllers\Api;

use App\Services\AnalyticsService;
use CodeIgniter\RESTful\ResourceController;

class AnalyticsController extends ResourceController
{
    private AnalyticsService $analyticsService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsService();
    }

    public function kpi()
    {
        return $this->respond($this->analyticsService->kpi($this->analyticsFilters()));
    }

    public function programme()
    {
        return $this->respond($this->analyticsService->alumniByProgramme($this->analyticsFilters()));
    }

    public function certificationsTrend()
    {
        return $this->respond($this->analyticsService->certificationsTrend($this->analyticsFilters()));
    }

    public function curriculumSkillsGapByProgramme()
    {
        return $this->respond($this->analyticsService->curriculumSkillsGapByProgramme($this->analyticsFilters()));
    }

    public function employment()
    {
        return $this->respond($this->analyticsService->topEmployers($this->analyticsFilters()));
    }

    public function jobTitles()
    {
        return $this->respond($this->analyticsService->jobTitles($this->analyticsFilters()));
    }

    public function graduation()
    {
        return $this->respond($this->analyticsService->graduationTrends($this->analyticsFilters()));
    }

    private function analyticsFilters(): array
    {
        $graduationYear = trim((string) ($this->request->getGet('graduation_year') ?? ''));

        return [
            'programme' => trim((string) ($this->request->getGet('programme') ?? '')),
            'graduation_year' => preg_match('/^\d{4}$/', $graduationYear) ? $graduationYear : '',
        ];
    }

}

