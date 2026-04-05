<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiUsageLogModel extends Model
{
    protected $table = 'api_usage_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'token_id',
        'method',
        'endpoint',
        'ip_address',
        'user_agent',
        'response_code',
        'created_at',
    ];

    protected $useTimestamps = false;

    public function logRequest(array $data): void
    {
        $this->insert([
            'user_id' => $data['user_id'] ?? null,
            'token_id' => $data['token_id'] ?? null,
            'method' => $data['method'] ?? '',
            'endpoint' => $data['endpoint'] ?? '',
            'ip_address' => $data['ip_address'] ?? '',
            'user_agent' => isset($data['user_agent']) ? mb_substr($data['user_agent'], 0, 500) : null,
            'response_code' => $data['response_code'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }


    public function getUserStats(int $userId): array
    {
        $totalRequests = $this->where('user_id', $userId)->countAllResults(false);

        $lastAccess = $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->first();

        $endpointBreakdown = $this->select('method, endpoint, COUNT(*) as hit_count')
            ->where('user_id', $userId)
            ->groupBy('method, endpoint')
            ->orderBy('hit_count', 'DESC')
            ->findAll();

        $recentActivity = $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->findAll();

        return [
            'total_requests' => $totalRequests,
            'last_access' => $lastAccess['created_at'] ?? null,
            'endpoint_breakdown' => $endpointBreakdown,
            'recent_activity' => $recentActivity,
        ];
    }

    public function getLoginHistory(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
            ->where('endpoint', 'api/auth/login')
            ->where('method', 'POST')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    public function getTokenUsageStats(int $userId): array
    {
        return $this->select('token_id, COUNT(*) as request_count, MIN(created_at) as first_used, MAX(created_at) as last_used')
            ->where('user_id', $userId)
            ->whereNotIn('token_id', [0])
            ->where('token_id IS NOT NULL')
            ->groupBy('token_id')
            ->orderBy('last_used', 'DESC')
            ->findAll();
    }
}
