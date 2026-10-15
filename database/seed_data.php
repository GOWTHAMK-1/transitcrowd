<?php
/**
 * CouchDB Demonstration Seed Data
 * Clearly labeled sample data for college open-source demonstration.
 */

declare(strict_types=1);

namespace Database;

class SeedData
{
    public static function getUsers(): array
    {
        return [
            [
                '_id' => 'user:admin-primary',
                'type' => 'user',
                'name' => 'System Administrator',
                'email' => 'admin@example.test',
                // Default password is 'Admin@123'
                'password_hash' => password_hash('Admin@123', PASSWORD_BCRYPT),
                'role' => 'admin',
                'created_at' => date('c', strtotime('-30 days')),
                'is_demo' => true,
            ],
            [
                '_id' => 'user:passenger-demo',
                'type' => 'user',
                'name' => 'Demo Passenger',
                'email' => 'passenger@example.test',
                // Default password is 'Passenger@123'
                'password_hash' => password_hash('Passenger@123', PASSWORD_BCRYPT),
                'role' => 'passenger',
                'created_at' => date('c', strtotime('-15 days')),
                'favorite_routes' => ['route:metro-blue', 'route:bus-101'],
                'favorite_stops' => ['stop:central-station', 'stop:tech-park'],
                'is_demo' => true,
            ],
        ];
    }

    public static function getStops(): array
    {
        return [
            [
                '_id' => 'stop:central-station',
                'type' => 'stop',
                'stop_code' => 'ST-001',
                'stop_name' => 'Central Multi-Modal Terminal',
                'latitude' => 12.9716,
                'longitude' => 77.5946,
                'transport_mode' => 'metro',
                'zone' => 'Zone 1 - Downtown',
                'facilities' => ['Wheelchair accessible', 'Ticket vending machine', 'Restrooms', 'Bicycle parking'],
                'is_demo' => true,
            ],
            [
                '_id' => 'stop:tech-park',
                'type' => 'stop',
                'stop_code' => 'ST-002',
                'stop_name' => 'Silicon Tech Park Station',
                'latitude' => 12.9850,
                'longitude' => 77.6050,
                'transport_mode' => 'metro',
                'zone' => 'Zone 2 - Commercial',
                'facilities' => ['Wheelchair accessible', 'Wi-Fi hotspot', 'Bus interchange'],
                'is_demo' => true,
            ],
            [
                '_id' => 'stop:university-square',
                'type' => 'stop',
                'stop_code' => 'ST-003',
                'stop_name' => 'University Square',
                'latitude' => 12.9600,
                'longitude' => 77.5800,
                'transport_mode' => 'metro',
                'zone' => 'Zone 1 - Academic',
                'facilities' => ['Student pass counter', 'Sheltered platform'],
                'is_demo' => true,
            ],
            [
                '_id' => 'stop:airport-road',
                'type' => 'stop',
                'stop_code' => 'ST-004',
                'stop_name' => 'Airport Expressway Link',
                'latitude' => 13.0100,
                'longitude' => 77.6300,
                'transport_mode' => 'bus',
                'zone' => 'Zone 3 - North Suburbs',
                'facilities' => ['Luggage drop-off link', 'Express check-in kiosk'],
                'is_demo' => true,
            ],
            [
                '_id' => 'stop:city-hospital',
                'type' => 'stop',
                'stop_code' => 'ST-005',
                'stop_name' => 'City General Hospital',
                'latitude' => 12.9550,
                'longitude' => 77.6100,
                'transport_mode' => 'bus',
                'zone' => 'Zone 2 - Medical Hub',
                'facilities' => ['Wheelchair ramps', 'Emergency medical shuttle'],
                'is_demo' => true,
            ],
            [
                '_id' => 'stop:green-valley',
                'type' => 'stop',
                'stop_code' => 'ST-006',
                'stop_name' => 'Green Valley Residential Suburb',
                'latitude' => 12.9400,
                'longitude' => 77.5700,
                'transport_mode' => 'bus',
                'zone' => 'Zone 4 - Residential South',
                'facilities' => ['Sheltered stop', 'EV charger'],
                'is_demo' => true,
            ],
            [
                '_id' => 'stop:grand-central-rail',
                'type' => 'stop',
                'stop_code' => 'ST-007',
                'stop_name' => 'Grand Central Suburban Depot',
                'latitude' => 12.9780,
                'longitude' => 77.5750,
                'transport_mode' => 'train',
                'zone' => 'Zone 1 - Rail Hub',
                'facilities' => ['Luggage storage', 'Waiting lounge', 'Express platforms'],
                'is_demo' => true,
            ],
            [
                '_id' => 'stop:east-junction',
                'type' => 'stop',
                'stop_code' => 'ST-008',
                'stop_name' => 'East Industrial Junction',
                'latitude' => 12.9650,
                'longitude' => 77.6600,
                'transport_mode' => 'train',
                'zone' => 'Zone 3 - Industrial Sector',
                'facilities' => ['Freight and commuter platforms', 'Bridge walkway'],
                'is_demo' => true,
            ],
        ];
    }

    public static function getRoutes(): array
    {
        return [
            [
                '_id' => 'route:metro-blue',
                'type' => 'route',
                'route_number' => 'M-BLU',
                'route_name' => 'Metro Line 1 (Blue Corridor)',
                'transport_mode' => 'metro',
                'description' => 'Fast mass transit connecting Central Terminal through Tech Park to North Hub.',
                'stop_ids' => ['stop:central-station', 'stop:tech-park', 'stop:university-square'],
                'frequency_minutes' => 6,
                'operating_hours' => '05:30 - 23:30',
                'vehicle_capacity' => 850,
                'active' => true,
                'peak_hours' => [
                    ['start' => '08:00', 'end' => '10:30', 'multiplier' => 1.8],
                    ['start' => '17:00', 'end' => '20:00', 'multiplier' => 1.9],
                ],
                'is_demo' => true,
            ],
            [
                '_id' => 'route:bus-101',
                'type' => 'route',
                'route_number' => 'B-101',
                'route_name' => 'Express City Bus 101',
                'transport_mode' => 'bus',
                'description' => 'Rapid arterial bus service from Central Station to Airport link via City Hospital.',
                'stop_ids' => ['stop:central-station', 'stop:city-hospital', 'stop:airport-road'],
                'frequency_minutes' => 12,
                'operating_hours' => '06:00 - 22:30',
                'vehicle_capacity' => 65,
                'active' => true,
                'peak_hours' => [
                    ['start' => '08:30', 'end' => '10:30', 'multiplier' => 1.6],
                    ['start' => '17:30', 'end' => '19:30', 'multiplier' => 1.7],
                ],
                'is_demo' => true,
            ],
            [
                '_id' => 'route:bus-204',
                'type' => 'route',
                'route_number' => 'B-204',
                'route_name' => 'Feeder Bus 204 (Green Valley Line)',
                'transport_mode' => 'bus',
                'description' => 'Neighborhood feeder connecting residential Green Valley to University Square and Central.',
                'stop_ids' => ['stop:green-valley', 'stop:university-square', 'stop:central-station'],
                'frequency_minutes' => 15,
                'operating_hours' => '06:30 - 21:30',
                'vehicle_capacity' => 45,
                'active' => true,
                'peak_hours' => [
                    ['start' => '07:30', 'end' => '09:30', 'multiplier' => 1.5],
                    ['start' => '16:30', 'end' => '18:30', 'multiplier' => 1.5],
                ],
                'is_demo' => true,
            ],
            [
                '_id' => 'route:train-s3',
                'type' => 'route',
                'route_number' => 'T-S3',
                'route_name' => 'Suburban Commuter Rail S3',
                'transport_mode' => 'train',
                'description' => 'Regional heavy rail service linking Grand Central to East Industrial Junction.',
                'stop_ids' => ['stop:grand-central-rail', 'stop:central-station', 'stop:east-junction'],
                'frequency_minutes' => 20,
                'operating_hours' => '05:00 - 23:00',
                'vehicle_capacity' => 1200,
                'active' => true,
                'peak_hours' => [
                    ['start' => '08:00', 'end' => '10:00', 'multiplier' => 2.0],
                    ['start' => '17:00', 'end' => '19:30', 'multiplier' => 2.1],
                ],
                'is_demo' => true,
            ],
        ];
    }

    /**
     * Generate synthetic but structured historical observations
     */
    public static function getObservations(): array
    {
        $observations = [];
        $routes = self::getRoutes();

        // Generate data for the past 14 days covering different time periods
        $baseDays = [0, 1, 2, 3, 4, 7, 8, 9, 10, 11]; // Sample days in recent weeks
        $timeSlots = [
            ['time' => '08:15', 'category' => 'high', 'occupancy' => 78, 'peak' => true],
            ['time' => '08:45', 'category' => 'very_high', 'occupancy' => 92, 'peak' => true],
            ['time' => '09:15', 'category' => 'high', 'occupancy' => 74, 'peak' => true],
            ['time' => '11:30', 'category' => 'moderate', 'occupancy' => 45, 'peak' => false],
            ['time' => '13:00', 'category' => 'low', 'occupancy' => 22, 'peak' => false],
            ['time' => '15:15', 'category' => 'moderate', 'occupancy' => 48, 'peak' => false],
            ['time' => '17:45', 'category' => 'high', 'occupancy' => 82, 'peak' => true],
            ['time' => '18:30', 'category' => 'very_high', 'occupancy' => 95, 'peak' => true],
            ['time' => '19:15', 'category' => 'high', 'occupancy' => 76, 'peak' => true],
            ['time' => '21:00', 'category' => 'low', 'occupancy' => 28, 'peak' => false],
        ];

        $counter = 1;
        foreach ($routes as $route) {
            foreach ($route['stop_ids'] as $stopId) {
                foreach ($baseDays as $dayOffset) {
                    $dateStr = date('Y-m-d', strtotime("-{$dayOffset} days"));
                    $dayOfWeek = (int)date('N', strtotime($dateStr)); // 1=Mon, 7=Sun
                    $isWeekend = ($dayOfWeek >= 6);

                    // Use comprehensive slots to provide observation coverage across morning, noon, and evening
                    $slotsToUse = $isWeekend ? [2, 3, 4, 5, 8, 9] : [0, 1, 2, 3, 4, 5, 6, 7, 8];

                    foreach ($slotsToUse as $slotIdx) {
                        $slot = $timeSlots[$slotIdx];
                        $cat = $slot['category'];
                        $occ = $slot['occupancy'];

                        // Weekend adjustment
                        if ($isWeekend) {
                            $occ = max(15, (int)round($occ * 0.6));
                            $cat = $occ <= 30 ? 'low' : ($occ <= 60 ? 'moderate' : 'high');
                        }

                        $timestamp = $dateStr . 'T' . $slot['time'] . ':00+00:00';
                        $obsId = sprintf('obs:demo-%04d', $counter++);

                        $observations[] = [
                            '_id' => $obsId,
                            'type' => 'observation',
                            'route_id' => $route['_id'],
                            'stop_id' => $stopId,
                            'observed_at' => $timestamp,
                            'crowd_category' => $cat,
                            'occupancy_percent' => $occ,
                            'capacity' => $route['vehicle_capacity'],
                            'source' => 'demonstration_sample',
                            'validated' => true,
                            'notes' => 'Synthetic historical calibration record for testing',
                            'is_demo' => true,
                        ];
                    }
                }
            }
        }

        return $observations;
    }

    public static function getCrowdReports(): array
    {
        return [
            [
                '_id' => 'report:demo-001',
                'type' => 'crowd_report',
                'route_id' => 'route:metro-blue',
                'stop_id' => 'stop:central-station',
                'transport_mode' => 'metro',
                'reported_at' => date('c', strtotime('-40 minutes')),
                'crowd_category' => 'high',
                'approximate_occupancy' => 75,
                'comments' => 'Platform very full, waiting for next train.',
                'status' => 'approved',
                'reporter_role' => 'passenger',
                'ip_hash' => hash('sha256', '127.0.0.1_salt'),
                'is_demo' => true,
            ],
            [
                '_id' => 'report:demo-002',
                'type' => 'crowd_report',
                'route_id' => 'route:bus-101',
                'stop_id' => 'stop:airport-road',
                'transport_mode' => 'bus',
                'reported_at' => date('c', strtotime('-25 minutes')),
                'crowd_category' => 'moderate',
                'approximate_occupancy' => 45,
                'comments' => 'Seats partially available near back.',
                'status' => 'approved',
                'reporter_role' => 'passenger',
                'ip_hash' => hash('sha256', '127.0.0.2_salt'),
                'is_demo' => true,
            ],
            [
                '_id' => 'report:demo-003',
                'type' => 'crowd_report',
                'route_id' => 'route:bus-204',
                'stop_id' => 'stop:green-valley',
                'transport_mode' => 'bus',
                'reported_at' => date('c', strtotime('-10 minutes')),
                'crowd_category' => 'very_high',
                'approximate_occupancy' => 95,
                'comments' => 'Bus skipped stop due to severe overcrowding.',
                'status' => 'pending',
                'reporter_role' => 'passenger',
                'ip_hash' => hash('sha256', '127.0.0.3_salt'),
                'is_demo' => true,
            ],
            [
                '_id' => 'report:demo-004',
                'type' => 'crowd_report',
                'route_id' => 'route:train-s3',
                'stop_id' => 'stop:east-junction',
                'transport_mode' => 'train',
                'reported_at' => date('c', strtotime('-5 minutes')),
                'crowd_category' => 'low',
                'approximate_occupancy' => 20,
                'comments' => 'Spacious and quiet journey today.',
                'status' => 'pending',
                'reporter_role' => 'passenger',
                'ip_hash' => hash('sha256', '127.0.0.4_salt'),
                'is_demo' => true,
            ],
        ];
    }

    public static function getPredictions(): array
    {
        return [
            [
                '_id' => 'pred:demo-001',
                'type' => 'prediction',
                'route_id' => 'route:metro-blue',
                'stop_id' => 'stop:central-station',
                'prediction_time' => date('c', strtotime('+30 minutes')),
                'horizon_minutes' => 30,
                'crowd_category' => 'high',
                'estimated_occupancy_percent' => 76,
                'method' => 'historical_time_slot_average',
                'sample_count' => 12,
                'data_quality' => 'reliable',
                'created_at' => date('c'),
                'is_demo' => true,
            ],
        ];
    }
}
