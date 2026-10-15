<?php
/**
 * CouchDB Mango Indexes Definition
 */

declare(strict_types=1);

namespace Database;

class Indexes
{
    public static function getIndexDefinitions(): array
    {
        return [
            'routes' => [
                [
                    'name' => 'idx_routes_active_mode',
                    'fields' => ['active', 'transport_mode', 'route_number'],
                ],
                [
                    'name' => 'idx_routes_number',
                    'fields' => ['route_number'],
                ],
            ],
            'stops' => [
                [
                    'name' => 'idx_stops_mode',
                    'fields' => ['transport_mode', 'stop_name'],
                ],
                [
                    'name' => 'idx_stops_name',
                    'fields' => ['stop_name'],
                ],
            ],
            'observations' => [
                [
                    'name' => 'idx_obs_route_stop_time',
                    'fields' => ['route_id', 'stop_id', 'observed_at'],
                ],
                [
                    'name' => 'idx_obs_route_time',
                    'fields' => ['route_id', 'observed_at'],
                ],
                [
                    'name' => 'idx_obs_stop_time',
                    'fields' => ['stop_id', 'observed_at'],
                ],
                [
                    'name' => 'idx_obs_crowd_category',
                    'fields' => ['crowd_category', 'observed_at'],
                ],
            ],
            'reports' => [
                [
                    'name' => 'idx_rep_status_time',
                    'fields' => ['status', 'reported_at'],
                ],
                [
                    'name' => 'idx_rep_route_stop',
                    'fields' => ['route_id', 'stop_id', 'reported_at'],
                ],
            ],
            'users' => [
                [
                    'name' => 'idx_users_email',
                    'fields' => ['email'],
                ],
                [
                    'name' => 'idx_users_role',
                    'fields' => ['role'],
                ],
            ],
            'predictions' => [
                [
                    'name' => 'idx_pred_route_stop_time',
                    'fields' => ['route_id', 'stop_id', 'prediction_time'],
                ],
            ],
        ];
    }
}
