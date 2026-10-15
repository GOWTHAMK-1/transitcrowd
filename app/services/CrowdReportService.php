<?php
/**
 * CrowdReportService - Manages passenger voluntary crowd submissions,
 * validation, spam/duplicate prevention, and administrative moderation.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use App\Validators\ValidationService;
use RuntimeException;

class CrowdReportService
{
    private CouchDBService $couch;
    private string $reportsDb;
    private string $obsDb;

    public function __construct(?CouchDBService $couch = null)
    {
        $this->couch = $couch ?? new CouchDBService();
        $this->reportsDb = $this->couch->getDatabaseName('reports');
        $this->obsDb = $this->couch->getDatabaseName('observations');
    }

    /**
     * Rate limiting check for submitting reports (e.g., max 5 submissions in 10 minutes)
     */
    public function checkRateLimit(string $ip): bool
    {
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $now = time();
        $key = 'rate_limit_report_' . md5($ip);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => $now];
        }

        if ($now - $_SESSION[$key]['first_attempt'] > 600) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => $now];
        }

        return $_SESSION[$key]['count'] < 5;
    }

    public function incrementRateLimit(string $ip): void
    {
        $key = 'rate_limit_report_' . md5($ip);
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }
        $_SESSION[$key]['count']++;
    }

    /**
     * Spam/Duplicate detection: checks if an identical report was submitted
     * for the same route and stop within the last 10 minutes by the same client.
     */
    public function isDuplicate(string $routeId, string $stopId, string $ipHash): bool
    {
        $tenMinutesAgo = date('c', strtotime('-10 minutes'));

        $docs = $this->couch->find($this->reportsDb, [
            'selector' => [
                'type' => 'crowd_report',
                'route_id' => $routeId,
                'stop_id' => $stopId,
                'ip_hash' => $ipHash,
                'reported_at' => ['$gte' => $tenMinutesAgo],
            ],
            'limit' => 1,
        ]);

        return !empty($docs);
    }

    /**
     * Submit a crowd report
     */
    public function submitReport(array $data, string $clientIp, ?array $currentUser = null): array
    {
        if (!$this->checkRateLimit($clientIp)) {
            return [
                'success' => false,
                'errors' => ['general' => ['Submission rate limit reached. Please wait before submitting another report.']],
            ];
        }

        $validator = new ValidationService();
        $routeId = $validator->validateRequired('route_id', $data['route_id'] ?? '', 'Route');
        $stopId = $validator->validateRequired('stop_id', $data['stop_id'] ?? '', 'Stop');
        $mode = $validator->validateInArray('transport_mode', $data['transport_mode'] ?? 'bus', ['bus', 'metro', 'train'], 'Transport Mode');
        $category = $validator->validateInArray('crowd_category', $data['crowd_category'] ?? '', ['low', 'moderate', 'high', 'very_high'], 'Crowd Level');

        $ipHash = hash('sha256', $clientIp . '_' . Config::get('APP_SECRET', 'secret'));

        // Check for duplicate
        if ($routeId && $stopId && $this->isDuplicate($routeId, $stopId, $ipHash)) {
            return [
                'success' => false,
                'errors' => ['general' => ['A similar report was recently submitted from your connection. Please wait 10 minutes.']],
            ];
        }

        if (!$validator->isValid()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors(),
            ];
        }

        $occupancy = null;
        if (isset($data['approximate_occupancy']) && is_numeric($data['approximate_occupancy'])) {
            $occupancy = max(0, min(100, (int)$data['approximate_occupancy']));
        }

        $comments = ValidationService::sanitizeString($data['comments'] ?? '');
        if (strlen($comments) > 300) {
            $comments = substr($comments, 0, 300);
        }

        $reportId = 'report:' . bin2hex(random_bytes(8));
        $doc = [
            '_id' => $reportId,
            'type' => 'crowd_report',
            'route_id' => $routeId,
            'stop_id' => $stopId,
            'transport_mode' => $mode,
            'reported_at' => date('c'),
            'observed_at' => !empty($data['observed_at']) ? date('c', strtotime($data['observed_at'])) : date('c'),
            'crowd_category' => $category,
            'approximate_occupancy' => $occupancy,
            'comments' => $comments,
            'status' => 'pending', // pending moderation
            'user_id' => $currentUser['id'] ?? null,
            'reporter_role' => $currentUser['role'] ?? 'passenger_anonymous',
            'ip_hash' => $ipHash,
            'is_demo' => false,
        ];

        $saveRes = $this->couch->saveDocument($this->reportsDb, $doc, $reportId);
        if (!$saveRes['ok']) {
            return [
                'success' => false,
                'errors' => ['general' => ['Database error saving report. Please try again.']],
            ];
        }

        $this->incrementRateLimit($clientIp);

        return [
            'success' => true,
            'report_id' => $reportId,
            'message' => 'Thank you! Your crowd report has been received and queued for review.',
        ];
    }

    /**
     * Get recent reports with optional filtering
     */
    public function getReports(array $filters = [], int $limit = 50): array
    {
        $selector = ['type' => 'crowd_report'];

        if (!empty($filters['status'])) {
            $selector['status'] = $filters['status'];
        }
        if (!empty($filters['route_id'])) {
            $selector['route_id'] = $filters['route_id'];
        }
        if (!empty($filters['stop_id'])) {
            $selector['stop_id'] = $filters['stop_id'];
        }

        $docs = $this->couch->find($this->reportsDb, [
            'selector' => $selector,
            'limit' => $limit,
        ]);

        // Sort descending by reported_at
        usort($docs, fn($a, $b) => strcmp($b['reported_at'] ?? '', $a['reported_at'] ?? ''));

        return $docs;
    }

    public function getReportById(string $reportId): ?array
    {
        $doc = $this->couch->getDocument($this->reportsDb, $reportId);
        if ($doc && ($doc['type'] ?? '') === 'crowd_report') {
            return $doc;
        }
        return null;
    }

    /**
     * Moderate report: approve, reject, or flag
     * When approved, optionally promote to validated observation
     */
    public function moderateReport(string $reportId, string $status, ?string $adminNotes = null): array
    {
        if (!in_array($status, ['approved', 'rejected', 'pending'], true)) {
            return ['success' => false, 'message' => 'Invalid moderation status'];
        }

        $report = $this->getReportById($reportId);
        if ($report === null) {
            return ['success' => false, 'message' => 'Report not found'];
        }

        $report['status'] = $status;
        $report['moderated_at'] = date('c');
        $report['admin_notes'] = ValidationService::sanitizeString($adminNotes ?? '');

        $updateRes = $this->couch->updateDocument($this->reportsDb, $report);
        if (!$updateRes['ok']) {
            return ['success' => false, 'message' => 'Failed to update report status'];
        }

        // If approved, convert to historical observation record
        if ($status === 'approved') {
            $obsId = 'obs:rep-' . substr($reportId, 7);
            $obsDoc = [
                '_id' => $obsId,
                'type' => 'observation',
                'route_id' => $report['route_id'],
                'stop_id' => $report['stop_id'],
                'observed_at' => $report['observed_at'] ?? $report['reported_at'],
                'crowd_category' => $report['crowd_category'],
                'occupancy_percent' => $report['approximate_occupancy'] ?? null,
                'source' => 'approved_passenger_report',
                'validated' => true,
                'report_ref' => $reportId,
                'created_at' => date('c'),
            ];
            $this->couch->saveDocument($this->obsDb, $obsDoc, $obsId);
        }

        return ['success' => true, 'status' => $status];
    }

    public function deleteReport(string $reportId): array
    {
        $res = $this->couch->deleteDocument($this->reportsDb, $reportId);
        return [
            'success' => $res['ok'],
            'message' => $res['ok'] ? 'Report deleted' : 'Failed to delete report',
        ];
    }
}
