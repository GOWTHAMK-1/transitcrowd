<?php
/**
 * RouteService - Manages transport routes, connections, and schedules
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use App\Validators\ValidationService;
use RuntimeException;

class RouteService
{
    private CouchDBService $couch;
    private StopService $stopService;
    private string $routesDb;

    public function __construct(?CouchDBService $couch = null, ?StopService $stopService = null)
    {
        $this->couch = $couch ?? new CouchDBService();
        $this->stopService = $stopService ?? new StopService($this->couch);
        $this->routesDb = $this->couch->getDatabaseName('routes');
    }

    public function getAllRoutes(array $filters = []): array
    {
        $selector = ['type' => 'route'];

        if (isset($filters['active'])) {
            $selector['active'] = (bool)$filters['active'];
        }
        if (!empty($filters['transport_mode'])) {
            $selector['transport_mode'] = $filters['transport_mode'];
        }

        $docs = $this->couch->find($this->routesDb, [
            'selector' => $selector,
            'limit' => 100,
        ]);

        if (!empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $docs = array_filter($docs, function ($route) use ($search) {
                $num = mb_strtolower($route['route_number'] ?? '');
                $name = mb_strtolower($route['route_name'] ?? '');
                $desc = mb_strtolower($route['description'] ?? '');
                return str_contains($num, $search) || str_contains($name, $search) || str_contains($desc, $search);
            });
            $docs = array_values($docs);
        }

        // Sort by route_number
        usort($docs, fn($a, $b) => strcmp($a['route_number'] ?? '', $b['route_number'] ?? ''));

        return $docs;
    }

    public function getRouteById(string $routeId, bool $withStops = true): ?array
    {
        $doc = $this->couch->getDocument($this->routesDb, $routeId);
        if ($doc === null || ($doc['type'] ?? '') !== 'route') {
            return null;
        }

        if ($withStops && !empty($doc['stop_ids'])) {
            $doc['stops'] = $this->stopService->getStopsByIds($doc['stop_ids']);
        } else {
            $doc['stops'] = [];
        }

        return $doc;
    }

    public function getRoutesByStopId(string $stopId): array
    {
        $all = $this->getAllRoutes(['active' => true]);
        $matching = [];

        foreach ($all as $route) {
            $stops = $route['stop_ids'] ?? [];
            if (in_array($stopId, $stops, true)) {
                $matching[] = $route;
            }
        }

        return $matching;
    }

    public function findRoutesBetweenStops(string $originStopId, string $destStopId): array
    {
        $all = $this->getAllRoutes(['active' => true]);
        $matching = [];

        foreach ($all as $route) {
            $stops = $route['stop_ids'] ?? [];
            $originIdx = array_search($originStopId, $stops, true);
            $destIdx = array_search($destStopId, $stops, true);

            if ($originIdx !== false && $destIdx !== false && $originIdx < $destIdx) {
                $route['origin_index'] = $originIdx;
                $route['destination_index'] = $destIdx;
                $route['stops_between_count'] = $destIdx - $originIdx;
                $matching[] = $route;
            }
        }

        return $matching;
    }

    public function createRoute(array $data): array
    {
        $validator = new ValidationService();
        $number = $validator->validateRequired('route_number', $data['route_number'] ?? '', 'Route Number');
        $name = $validator->validateRequired('route_name', $data['route_name'] ?? '', 'Route Name');
        $mode = $validator->validateInArray('transport_mode', $data['transport_mode'] ?? 'bus', ['bus', 'metro', 'train'], 'Transport Mode');

        if (!$validator->isValid()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $id = !empty($data['_id']) ? $data['_id'] : 'route:' . bin2hex(random_bytes(6));
        $doc = [
            '_id' => $id,
            'type' => 'route',
            'route_number' => ValidationService::sanitizeString($number),
            'route_name' => ValidationService::sanitizeString($name),
            'transport_mode' => $mode,
            'description' => ValidationService::sanitizeString($data['description'] ?? ''),
            'stop_ids' => is_array($data['stop_ids'] ?? null) ? $data['stop_ids'] : [],
            'frequency_minutes' => isset($data['frequency_minutes']) ? (int)$data['frequency_minutes'] : 15,
            'operating_hours' => ValidationService::sanitizeString($data['operating_hours'] ?? '06:00 - 22:00'),
            'vehicle_capacity' => isset($data['vehicle_capacity']) ? (int)$data['vehicle_capacity'] : null,
            'active' => isset($data['active']) ? (bool)$data['active'] : true,
            'peak_hours' => is_array($data['peak_hours'] ?? null) ? $data['peak_hours'] : [],
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        $res = $this->couch->saveDocument($this->routesDb, $doc, $id);
        if ($res['ok']) {
            return ['success' => true, 'route' => $doc];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to save route into CouchDB']]];
    }

    public function updateRoute(string $routeId, array $data): array
    {
        $existing = $this->getRouteById($routeId, false);
        if ($existing === null) {
            return ['success' => false, 'errors' => ['general' => ['Route not found']]];
        }

        if (isset($data['route_number'])) {
            $existing['route_number'] = ValidationService::sanitizeString($data['route_number']);
        }
        if (isset($data['route_name'])) {
            $existing['route_name'] = ValidationService::sanitizeString($data['route_name']);
        }
        if (isset($data['transport_mode'])) {
            $existing['transport_mode'] = ValidationService::sanitizeString($data['transport_mode']);
        }
        if (isset($data['description'])) {
            $existing['description'] = ValidationService::sanitizeString($data['description']);
        }
        if (isset($data['stop_ids']) && is_array($data['stop_ids'])) {
            $existing['stop_ids'] = $data['stop_ids'];
        }
        if (isset($data['frequency_minutes'])) {
            $existing['frequency_minutes'] = (int)$data['frequency_minutes'];
        }
        if (isset($data['operating_hours'])) {
            $existing['operating_hours'] = ValidationService::sanitizeString($data['operating_hours']);
        }
        if (isset($data['vehicle_capacity'])) {
            $existing['vehicle_capacity'] = is_numeric($data['vehicle_capacity']) ? (int)$data['vehicle_capacity'] : null;
        }
        if (isset($data['active'])) {
            $existing['active'] = (bool)$data['active'];
        }
        $existing['updated_at'] = date('c');

        $res = $this->couch->updateDocument($this->routesDb, $existing);
        if ($res['ok']) {
            return ['success' => true, 'route' => $existing];
        }

        return ['success' => false, 'errors' => ['general' => ['Failed to update route in CouchDB']]];
    }

    public function deleteRoute(string $routeId): array
    {
        $res = $this->couch->deleteDocument($this->routesDb, $routeId);
        return [
            'success' => $res['ok'],
            'message' => $res['ok'] ? 'Route deleted successfully' : 'Failed to delete route',
        ];
    }
}
