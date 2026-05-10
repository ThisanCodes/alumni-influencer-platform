<?php

namespace App\Models;

class AlumniProfileModel extends BaseProfileModel
{
    protected $table = 'alumni_profiles';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'first_name', 'last_name', 'bio', 'linkedin_url', 'profile_image',
        'is_featured', 'appearance_count', 'created_at', 'updated_at',
    ];
    protected $useTimestamps = true;
    protected $returnType = 'array';

    protected $validationRules = [
        'first_name' => 'required|max_length[255]',
        'last_name' => 'required|max_length[255]',
        'bio' => 'permit_empty',
        'linkedin_url' => 'permit_empty|valid_url_strict',
        'profile_image' => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'first_name'=> [
            'required' => 'First name is required.',
            'max_length' => 'First name cannot exceed 255 characters.'
        ],
        'last_name' => [
            'required' => 'Last name is required.',
            'max_length' => 'Last name cannot exceed 255 characters.'
        ],
        'linkedin_url' => [
            'valid_url_strict' => 'LinkedIn URL must be a valid URL.'
        ],
        'profile_image' => [
            'mime_in' => 'Only JPEG and PNG images are allowed.',
            'is_image' => 'File must be a valid image.'
        ]
    ];

    public function findSummaries(array $filters = []): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $programme = trim((string) ($filters['programme'] ?? ''));
        $graduationYear = trim((string) ($filters['graduation_year'] ?? ''));
        $industry = trim((string) ($filters['industry'] ?? ''));

        $builder = $this
            ->select(
                "alumni_profiles.*, users.email,
                (SELECT d.title FROM degrees d WHERE d.user_id = alumni_profiles.user_id ORDER BY d.completion_date DESC NULLS LAST, d.id DESC LIMIT 1) AS latest_degree,
                (SELECT d.completion_date FROM degrees d WHERE d.user_id = alumni_profiles.user_id ORDER BY d.completion_date DESC NULLS LAST, d.id DESC LIMIT 1) AS latest_degree_graduation_date,
                (SELECT eh.role FROM employment_history eh WHERE eh.user_id = alumni_profiles.user_id ORDER BY eh.is_current DESC, eh.start_date DESC NULLS LAST, eh.id DESC LIMIT 1) AS current_industry",
                false
            )
            ->join('users', 'users.id = alumni_profiles.user_id', 'left');

        if ($search !== '') {
            $builder
                ->groupStart()
                ->like('alumni_profiles.first_name', $search)
                ->orLike('alumni_profiles.last_name', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        if ($programme !== '') {
            $builder->where(
                "EXISTS (SELECT 1 FROM degrees d WHERE d.user_id = alumni_profiles.user_id AND d.title = " . $this->db->escape($programme) . ")",
                null,
                false
            );
        }

        if (preg_match('/^\d{4}$/', $graduationYear)) {
            $builder->where(
                "EXISTS (SELECT 1 FROM degrees d WHERE d.user_id = alumni_profiles.user_id AND EXTRACT(YEAR FROM d.completion_date) = " . (int) $graduationYear . ")",
                null,
                false
            );
        }

        if ($industry !== '') {
            $builder->where(
                "EXISTS (SELECT 1 FROM employment_history eh WHERE eh.user_id = alumni_profiles.user_id AND eh.role = " . $this->db->escape($industry) . ")",
                null,
                false
            );
        }

        return $builder
            ->orderBy('alumni_profiles.created_at', 'DESC')
            ->findAll();
    }

    public function findFilterOptions(): array
    {
        $programmeRows = $this->db->table('degrees')
            ->select("DISTINCT title AS programme", false)
            ->where('title IS NOT NULL', null, false)
            ->where("title <> ''", null, false)
            ->orderBy('title', 'ASC')
            ->get()
            ->getResultArray();

        $industryRows = $this->db->table('employment_history')
            ->select("DISTINCT role AS industry", false)
            ->where('role IS NOT NULL', null, false)
            ->where("role <> ''", null, false)
            ->orderBy('role', 'ASC')
            ->get()
            ->getResultArray();

        $graduationRows = $this->db->table('degrees')
            ->select('DISTINCT EXTRACT(YEAR FROM completion_date) AS graduation_year', false)
            ->where('completion_date IS NOT NULL', null, false)
            ->orderBy('graduation_year', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'programmes' => array_values(array_map(
                static fn ($row) => (string) $row['programme'],
                $programmeRows
            )),
            'industries' => array_values(array_map(
                static fn ($row) => (string) $row['industry'],
                $industryRows
            )),
            'graduation_years' => array_values(array_map(
                static fn ($row) => (string) $row['graduation_year'],
                $graduationRows
            )),
        ];
    }

    public function findFeaturedProfile(): ?array
    {
        $featured = $this->where('is_featured', true)->first();

        if (!$featured) {
            return null;
        }

        $userId = (int) $featured['user_id'];
        $winningBid = $this->findLatestWinningBid($userId);

        return array_merge($featured, [
            'winning_bid_amount' => $winningBid['amount'] ?? null,
            'featured_at' => $winningBid['selected_at'] ?? null,
            'degrees' => $this->findRelatedRows('degrees', $userId, 'completion_date'),
            'certifications' => $this->findRelatedRows('certifications', $userId, 'completion_date'),
            'licences' => $this->findRelatedRows('licences', $userId, 'completion_date'),
            'courses' => $this->findRelatedRows('courses', $userId, 'completion_date'),
            'employment_history' => $this->findRelatedRows('employment_history', $userId, 'start_date'),
        ]);
    }

    private function findLatestWinningBid(int $userId): ?array
    {
        $row = $this->db->table('bid_winners bw')
            ->select('b.amount, bw.selected_at')
            ->join('bids b', 'b.id = bw.bid_id')
            ->where('b.user_id', $userId)
            ->orderBy('bw.selected_at', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function findRelatedRows(string $table, int $userId, string $orderColumn): array
    {
        return $this->db->table($table)
            ->where('user_id', $userId)
            ->orderBy($orderColumn, 'DESC')
            ->get()
            ->getResultArray();
    }
}