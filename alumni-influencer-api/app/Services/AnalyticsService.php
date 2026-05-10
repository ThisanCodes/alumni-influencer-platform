<?php

namespace App\Services;

use App\Models\BidModel;
use Config\Database;

class AnalyticsService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function kpi(array $filters): array
    {
        $totalAlumniBuilder = $this->db->table('alumni_profiles ap');
        $this->applyAlumniFilters($totalAlumniBuilder, 'ap.user_id', $filters);

        $activeBiddersBuilder = $this->db->table('bids b')
            ->select('user_id')
            ->where('status', BidModel::STATUS['ACTIVE']);
        $this->applyAlumniFilters($activeBiddersBuilder, 'b.user_id', $filters);

        $monthStart = date('Y-m-01 00:00:00');
        $nextMonthStart = date('Y-m-01 00:00:00', strtotime('first day of next month'));
        $bidsThisMonthBuilder = $this->db->table('bids b')
            ->where('created_at >=', $monthStart)
            ->where('created_at <', $nextMonthStart);
        $this->applyAlumniFilters($bidsThisMonthBuilder, 'b.user_id', $filters);

        $featuredProfile = $this->db->table('alumni_profiles')
            ->select("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) AS full_name", false)
            ->where('is_featured', true)
            ->get()
            ->getRowArray();

        return [
            'total_alumni' => (int) $totalAlumniBuilder->countAllResults(),
            'active_bidders' => (int) $activeBiddersBuilder->groupBy('user_id')->countAllResults(),
            'bids_this_month' => (int) $bidsThisMonthBuilder->countAllResults(),
            'featured_profile' => $featuredProfile['full_name'] ?? 'None',
        ];
    }

    public function alumniByProgramme(array $filters): array
    {
        $builder = $this->db->table('degrees d')
            ->select("COALESCE(NULLIF(title, ''), 'Unknown') AS programme, COUNT(*) AS count", false)
            ->groupBy("COALESCE(NULLIF(title, ''), 'Unknown')", false);
        $this->applyAlumniFilters($builder, 'd.user_id', $filters);

        return array_map(static fn ($row) => [
            'programme' => (string) $row['programme'],
            'count' => (int) $row['count'],
        ], $builder->orderBy('count', 'DESC')->get()->getResultArray());
    }

    public function certificationsTrend(array $filters): array
    {
        $builder = $this->db->table('certifications c')
            ->select('EXTRACT(YEAR FROM completion_date) AS year, COUNT(*) AS count', false)
            ->where('completion_date IS NOT NULL', null, false);
        $this->applyAlumniFilters($builder, 'c.user_id', $filters);

        return array_map(static fn ($row) => [
            'year' => (int) $row['year'],
            'count' => (int) $row['count'],
        ], $builder
            ->groupBy('EXTRACT(YEAR FROM completion_date)', false)
            ->orderBy('year', 'ASC')
            ->get()
            ->getResultArray());
    }

    public function curriculumSkillsGapByProgramme(array $filters): array
    {
        $conditions = [
            "d.title IS NOT NULL",
            "d.title <> ''",
        ];

        if (($filters['programme'] ?? '') !== '') {
            $conditions[] = 'd.title = ' . $this->db->escape($filters['programme']);
        }

        if (($filters['graduation_year'] ?? '') !== '') {
            $conditions[] = 'EXTRACT(YEAR FROM d.completion_date) = ' . (int) $filters['graduation_year'];
        }

        $sql = "
            SELECT
                programme_users.programme,
                ROUND(AVG(COALESCE(certifications.certification_count, 0))) AS avg_certifications,
                ROUND(AVG(COALESCE(licences.licence_count, 0))) AS avg_licences,
                ROUND(AVG(COALESCE(courses.course_count, 0))) AS avg_courses
            FROM (
                SELECT DISTINCT d.user_id, d.title AS programme
                FROM degrees d
                WHERE " . implode(' AND ', $conditions) . "
            ) programme_users
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS certification_count
                FROM certifications
                GROUP BY user_id
            ) certifications ON certifications.user_id = programme_users.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS licence_count
                FROM licences
                GROUP BY user_id
            ) licences ON licences.user_id = programme_users.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS course_count
                FROM courses
                GROUP BY user_id
            ) courses ON courses.user_id = programme_users.user_id
            GROUP BY programme_users.programme
            ORDER BY programme_users.programme ASC
        ";

        return array_map(static fn ($row) => [
            'programme' => (string) $row['programme'],
            'avg_certifications' => (int) $row['avg_certifications'],
            'avg_licences' => (int) $row['avg_licences'],
            'avg_courses' => (int) $row['avg_courses'],
        ], $this->db->query($sql)->getResultArray());
    }

    public function topEmployers(array $filters): array
    {
        $builder = $this->db->table('employment_history eh')
            ->select("COALESCE(NULLIF(company, ''), 'Unknown') AS sector, COUNT(*) AS count", false)
            ->where('eh.is_current', true)
            ->groupBy("COALESCE(NULLIF(company, ''), 'Unknown')", false);
        $this->applyAlumniFilters($builder, 'eh.user_id', $filters);

        return array_map(static fn ($row) => [
            'sector' => (string) $row['sector'],
            'count' => (int) $row['count'],
        ], $builder->orderBy('count', 'DESC')->get()->getResultArray());
    }

    public function jobTitles(array $filters): array
    {
        $builder = $this->db->table('employment_history eh')
            ->select("COALESCE(NULLIF(role, ''), 'Unknown') AS job_title, COUNT(*) AS count", false)
            ->where('eh.is_current', true)
            ->groupBy("COALESCE(NULLIF(role, ''), 'Unknown')", false);
        $this->applyAlumniFilters($builder, 'eh.user_id', $filters);

        return array_map(static fn ($row) => [
            'job_title' => (string) $row['job_title'],
            'count' => (int) $row['count'],
        ], $builder->orderBy('count', 'DESC')->get()->getResultArray());
    }

    public function graduationTrends(array $filters): array
    {
        $builder = $this->db->table('degrees d')
            ->select('EXTRACT(YEAR FROM completion_date) AS year, COUNT(*) AS count', false)
            ->where('completion_date IS NOT NULL', null, false);
        $this->applyAlumniFilters($builder, 'd.user_id', $filters);

        return array_map(static fn ($row) => [
            'year' => (int) $row['year'],
            'count' => (int) $row['count'],
        ], $builder
            ->groupBy('EXTRACT(YEAR FROM completion_date)', false)
            ->orderBy('year', 'ASC')
            ->get()
            ->getResultArray());
    }

    private function applyAlumniFilters($builder, string $userColumn, array $filters): void
    {
        $filterSql = $this->alumniFilterSql($userColumn, $filters);

        if ($filterSql !== '') {
            $builder->where(substr($filterSql, 5), null, false);
        }
    }

    private function alumniFilterSql(string $userColumn, array $filters): string
    {
        $conditions = [];

        if (($filters['programme'] ?? '') !== '') {
            $conditions[] = 'fd.title = ' . $this->db->escape($filters['programme']);
        }

        if (($filters['graduation_year'] ?? '') !== '') {
            $conditions[] = 'EXTRACT(YEAR FROM fd.completion_date) = ' . (int) $filters['graduation_year'];
        }

        if (empty($conditions)) {
            return '';
        }

        return ' AND EXISTS (SELECT 1 FROM degrees fd WHERE fd.user_id = ' . $userColumn . ' AND ' . implode(' AND ', $conditions) . ')';
    }
}
