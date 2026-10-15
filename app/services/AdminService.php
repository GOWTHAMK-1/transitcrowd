<?php
/**
 * AdminService - Administrative operations, system health, and database metrics
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use RuntimeException;

class AdminService
{
    private CouchDBService $couch;
    private AnalyticsService $analytics;

    public function __construct(?CouchDBService $couch = null, ?AnalyticsService $analytics = null)
    {
        $this->couch = $couch ?? new CouchDBService();
        $this->analytics = $analytics ?? new AnalyticsService($this->couch);
    }

    /**
     * Get system health and database statistics from CouchDB
     */
    public function getSystemHealth(): array
    {
        $conn = $this->couch->testConnection();
        $databases = [
            'routes' => $this->couch->getDatabaseName('routes'),
            'stops' => $this->couch->getDatabaseName('stops'),
            'observations' => $this->couch->getDatabaseName('observations'),
            'reports' => $this->couch->getDatabaseName('reports'),
            'users' => $this->couch->getDatabaseName('users'),
            'predictions' => $this->couch->getDatabaseName('predictions'),
        ];

        $dbStats = [];
        $totalDocs = 0;
        $totalDiskSize = 0;

        foreach ($databases as $key => $name) {
            $info = $this->couch->getDbInfo($name);
            if ($info !== null) {
                $docCount = $info['doc_count'] ?? 0;
                $diskSize = $info['sizes']['file'] ?? ($info['disk_size'] ?? 0);
                $totalDocs += $docCount;
                $totalDiskSize += $diskSize;

                $dbStats[$key] = [
                    'name' => $name,
                    'exists' => true,
                    'doc_count' => $docCount,
                    'disk_size_bytes' => $diskSize,
                    'disk_size_formatted' => $this->formatBytes($diskSize),
                ];
            } else {
                $dbStats[$key] = [
                    'name' => $name,
                    'exists' => false,
                    'doc_count' => 0,
                    'disk_size_bytes' => 0,
                    'disk_size_formatted' => '0 B',
                ];
            }
        }

        return [
            'couchdb' => [
                'status' => $conn['success'] ? 'online' : 'offline',
                'version' => $conn['version'] ?? 'unknown',
                'host' => Config::get('COUCHDB_HOST', '127.0.0.1'),
                'port' => Config::get('COUCHDB_PORT', 5984),
                'fauxton_url' => 'http://' . Config::get('COUCHDB_HOST', '127.0.0.1') . ':' . Config::get('COUCHDB_PORT', 5984) . '/_utils/',
            ],
            'php' => [
                'version' => PHP_VERSION,
                'curl_enabled' => extension_loaded('curl'),
                'session_enabled' => extension_loaded('session'),
                'timezone' => date_default_timezone_get(),
            ],
            'databases' => $dbStats,
            'summary' => [
                'total_documents' => $totalDocs,
                'total_disk_size' => $this->formatBytes($totalDiskSize),
            ],
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
