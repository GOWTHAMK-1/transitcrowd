<?php
/**
 * CouchDBService - Apache CouchDB 3.x REST Client via PHP cURL
 * Provides secure, robust HTTP interaction with CouchDB.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use RuntimeException;
use Exception;

class CouchDBService
{
    private string $baseUrl;
    private string $user;
    private string $password;
    private int $timeout;
    private array $databases;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $config = Config::getCouchDbConfig();
        }

        $protocol = $config['protocol'] ?? 'http';
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int)($config['port'] ?? 5984);

        $this->baseUrl = sprintf('%s://%s:%d', $protocol, $host, $port);
        $this->user = (string)($config['user'] ?? 'admin');
        $this->password = (string)($config['password'] ?? 'admin');
        $this->timeout = (int)($config['timeout'] ?? 8);
        $this->databases = $config['databases'] ?? [
            'routes' => 'transport_routes',
            'stops' => 'transport_stops',
            'observations' => 'transport_observations',
            'reports' => 'transport_reports',
            'users' => 'transport_users',
            'predictions' => 'transport_predictions',
        ];
    }

    public function getDatabaseName(string $key): string
    {
        return $this->databases[$key] ?? $key;
    }

    public function getAllDatabaseNames(): array
    {
        return array_values($this->databases);
    }

    /**
     * Test connection to CouchDB
     */
    public function testConnection(): array
    {
        try {
            $response = $this->request('GET', '/');
            if ($response['status'] >= 200 && $response['status'] < 300 && isset($response['body']['couchdb'])) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to CouchDB server',
                    'version' => $response['body']['version'] ?? 'unknown',
                    'features' => $response['body']['features'] ?? [],
                    'status_code' => $response['status'],
                ];
            }
            return [
                'success' => false,
                'message' => 'Connected to host, but CouchDB welcome banner was not returned',
                'status_code' => $response['status'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'status_code' => 0,
            ];
        }
    }

    /**
     * Perform HTTP request to CouchDB via cURL
     */
    public function request(string $method, string $path, mixed $body = null, array $queryParams = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize PHP cURL handle.');
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: PublicTransportCrowdPredictor/1.0',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);

        if ($this->user !== '') {
            curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        $upperMethod = strtoupper($method);
        switch ($upperMethod) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body !== null) {
                    $json = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                    $headers[] = 'Content-Type: application/json';
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($body !== null) {
                    $json = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                    $headers[] = 'Content-Type: application/json';
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
                curl_setopt($ch, CURLOPT_NOBODY, true);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $upperMethod);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $rawOutput = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0) {
            throw new RuntimeException("cURL error ({$curlErrno}) connecting to CouchDB at {$this->baseUrl}: {$curlError}");
        }

        $decoded = null;
        if (is_string($rawOutput) && trim($rawOutput) !== '') {
            $decoded = json_decode($rawOutput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded = ['raw' => $rawOutput];
            }
        }

        return [
            'status' => $statusCode,
            'body' => $decoded,
            'ok' => ($statusCode >= 200 && $statusCode < 300),
        ];
    }

    public function get(string $path, array $queryParams = []): array
    {
        return $this->request('GET', $path, null, $queryParams);
    }

    public function post(string $path, mixed $body = null): array
    {
        return $this->request('POST', $path, $body);
    }

    public function put(string $path, mixed $body = null): array
    {
        return $this->request('PUT', $path, $body);
    }

    public function delete(string $path, array $queryParams = []): array
    {
        return $this->request('DELETE', $path, null, $queryParams);
    }

    public function listDbs(): array
    {
        $res = $this->get('/_all_dbs');
        return is_array($res['body']) ? $res['body'] : [];
    }

    public function dbExists(string $dbName): bool
    {
        try {
            $res = $this->request('HEAD', '/' . urlencode($dbName));
            return $res['status'] === 200;
        } catch (Exception) {
            return false;
        }
    }

    public function createDb(string $dbName): array
    {
        if ($this->dbExists($dbName)) {
            return ['status' => 200, 'ok' => true, 'already_existed' => true];
        }
        $res = $this->put('/' . urlencode($dbName));
        if ($res['status'] === 201 || $res['status'] === 200) {
            return ['status' => $res['status'], 'ok' => true, 'already_existed' => false];
        }
        return ['status' => $res['status'], 'ok' => false, 'error' => $res['body']['error'] ?? 'unknown', 'reason' => $res['body']['reason'] ?? 'Failed to create database'];
    }

    public function deleteDb(string $dbName): array
    {
        return $this->delete('/' . urlencode($dbName));
    }

    public function getDbInfo(string $dbName): ?array
    {
        $res = $this->get('/' . urlencode($dbName));
        return $res['ok'] ? $res['body'] : null;
    }

    public function getDocument(string $dbName, string $docId): ?array
    {
        $res = $this->get('/' . urlencode($dbName) . '/' . urlencode($docId));
        return $res['ok'] ? $res['body'] : null;
    }

    public function saveDocument(string $dbName, array $doc, ?string $docId = null): array
    {
        if ($docId !== null || isset($doc['_id'])) {
            $id = $docId ?? $doc['_id'];
            $res = $this->put('/' . urlencode($dbName) . '/' . urlencode($id), $doc);
        } else {
            $res = $this->post('/' . urlencode($dbName), $doc);
        }
        return $res;
    }

    public function updateDocument(string $dbName, array $doc): array
    {
        if (!isset($doc['_id'])) {
            throw new RuntimeException('Cannot update document without _id');
        }
        if (!isset($doc['_rev'])) {
            // Fetch current rev if not provided
            $existing = $this->getDocument($dbName, $doc['_id']);
            if ($existing && isset($existing['_rev'])) {
                $doc['_rev'] = $existing['_rev'];
            }
        }
        return $this->put('/' . urlencode($dbName) . '/' . urlencode($doc['_id']), $doc);
    }

    public function deleteDocument(string $dbName, string $docId, ?string $rev = null): array
    {
        if ($rev === null) {
            $existing = $this->getDocument($dbName, $docId);
            if ($existing && isset($existing['_rev'])) {
                $rev = $existing['_rev'];
            } else {
                return ['status' => 404, 'ok' => false, 'reason' => 'Document not found'];
            }
        }
        return $this->delete('/' . urlencode($dbName) . '/' . urlencode($docId), ['rev' => $rev]);
    }

    /**
     * Mango query execution via POST /{db}/_find
     */
    public function find(string $dbName, array $query): array
    {
        $res = $this->post('/' . urlencode($dbName) . '/_find', $query);
        if ($res['ok'] && isset($res['body']['docs']) && is_array($res['body']['docs'])) {
            return $res['body']['docs'];
        }
        return [];
    }

    /**
     * Create a Mango index: POST /{db}/_index
     */
    public function createIndex(string $dbName, array $fields, ?string $name = null, string $type = 'json'): array
    {
        $payload = [
            'index' => [
                'fields' => $fields,
            ],
            'type' => $type,
        ];
        if ($name !== null) {
            $payload['name'] = $name;
            $payload['ddoc'] = 'idx_' . $name;
        }

        return $this->post('/' . urlencode($dbName) . '/_index', $payload);
    }

    /**
     * List all indexes in a database: GET /{db}/_index
     */
    public function listIndexes(string $dbName): array
    {
        $res = $this->get('/' . urlencode($dbName) . '/_index');
        if ($res['ok'] && isset($res['body']['indexes']) && is_array($res['body']['indexes'])) {
            return $res['body']['indexes'];
        }
        return [];
    }

    /**
     * Bulk insert or update documents: POST /{db}/_bulk_docs
     */
    public function bulkDocs(string $dbName, array $docs): array
    {
        return $this->post('/' . urlencode($dbName) . '/_bulk_docs', ['docs' => $docs]);
    }
}
