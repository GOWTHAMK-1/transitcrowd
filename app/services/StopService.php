<?php
/**
 * StopService - Manages transport stops and stations
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use App\Validators\ValidationService;
use RuntimeException;

class StopService
{
    private CouchDBService $couch;
    private string $stopsDb;

    public function __construct(?CouchDBService $couch = null)
    {
        $this->couch = $couch ?? new CouchDBService();
        $this->stopsDb = $this->couch->getDatabaseName('stops');
    }

    public function getAllStops(array $filters = []): array
    {
        $selector = ['type' => 'stop'];

        if (!empty($filters['transport_mode'])) {
            $selector['transport_mode'] = $filters['transport_mode'];
        }

        $docs = $this->couch->find($this->stopsDb, [
            'selector' => $selector,
            'limit' => 100,
        ]);

        if (!empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $docs = array_filter($docs, function ($stop) use ($search) {
                $name = mb_strtolower($stop['stop_name'] ?? '');
                $code = mb_strtolower($stop['stop_code'] ?? '');
                $zone = mb_strtolower($stop['zone'] ?? '');
                return str_contains($name, $search) || str_contains($code, $search) || str_contains($zone, $search);
            });
            $docs = array_values($docs);
        }

        // Sort alphabetically by stop_name
        usort($docs, fn($a, $b) => strcmp($a['stop_name'] ?? '', $b['stop_name'] ?? ''));

        return $docs;
    }

    public function getStopById(string $stopId): ?array
    {
        $doc = $this->couch->getDocument($this->stopsDb, $stopId);
        if ($doc && ($doc['type'] ?? '') === 'stop') {
            return $doc;
        }
        return null;
    }

    public function getStopsByIds(array $stopIds): array
    {
        if (empty($stopIds)) {
            return [];
        }

        $result = [];
        foreach ($stopIds as $id) {
            $stop = $this->getStopById($id);
            if ($stop !== null) {
                $result[] = $stop;
            }
        }
        return $result;
    }

    public function createStop(array $data): array
    {
        $validator = new ValidationService();
        $name = $validator->validateRequired('stop_name', $data['stop_name'] ?? '', 'Stop Name');
        $mode = $validator->validateInArray('transport_mode', $data['transport_mode'] ?? 'bus', ['bus', 'metro', 'train'], 'Transport Mode');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $id = !empty($data['_id']) ? $data['_id'] : 'stop:' . bin2hex(random_bytes(6));
        $doc = [
            '_id' => $id,
            'type' => 'stop',
            'stop_code' => ValidationService::sanitizeString($data['stop_code'] ?? 'ST-' . rand(100, 999)),
            'stop_name' => $name,
            'transport_mode' => $mode,
            'latitude' => isset($data['latitude']) && is_numeric($data['latitude']) ? (float)$data['latitude'] : null,
            'longitude' => isset($data['longitude']) && is_numeric($data['longitude']) ? (float)$data['longitude'] : null,
            'zone' => ValidationService::sanitizeString($data['zone'] ?? 'Standard Zone'),
            'facilities' => is_array($data['facilities'] ?? null) ? $data['facilities'] : [],
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        $res = $this->couch->saveDocument($this->stopsDb, $doc, $id);
        if ($res['ok']) {
            return ['success' => true, 'stop' => $doc];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to save stop into CouchDB']]];
    }

    public function updateStop(string $stopId, array $data): array
    {
        $existing = $this->getStopById($stopId);
        if ($existing === null) {
            return ['success' => false, 'errors' => ['general' => ['Stop not found']]];
        }

        if (isset($data['stop_name'])) {
            $existing['stop_name'] = ValidationService::sanitizeString($data['stop_name']);
        }
        if (isset($data['transport_mode'])) {
            $existing['transport_mode'] = ValidationService::sanitizeString($data['transport_mode']);
        }
        if (isset($data['latitude'])) {
            $existing['latitude'] = is_numeric($data['latitude']) ? (float)$data['latitude'] : null;
        }
        if (isset($data['longitude'])) {
            $existing['longitude'] = is_numeric($data['longitude']) ? (float)$data['longitude'] : null;
        }
        if (isset($data['zone'])) {
            $existing['zone'] = ValidationService::sanitizeString($data['zone']);
        }
        $existing['updated_at'] = date('c');

        $res = $this->couch->updateDocument($this->stopsDb, $existing);
        if ($res['ok']) {
            return ['success' => true, 'stop' => $existing];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to update stop in CouchDB']]];
    }

    public function deleteStop(string $stopId): array
    {
        $res = $this->couch->deleteDocument($this->stopsDb, $stopId);
        return [
            'success' => $res['ok'],
            'message' => $res['ok'] ? 'Stop deleted successfully' : 'Failed to delete stop',
        ];
    }
}
