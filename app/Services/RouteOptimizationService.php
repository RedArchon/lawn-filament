<?php

namespace App\Services;

use App\Models\Property;
use App\Models\ServiceAppointment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class RouteOptimizationService
{
    public function optimize(Collection $properties, ?string $startLocation = null): array
    {
        $apiKey = config('services.google.api_key');

        if (empty($apiKey)) {
            throw new Exception('Google API key is not configured');
        }

        // Validate all properties are geocoded
        foreach ($properties as $property) {
            if (! $property->latitude || ! $property->longitude) {
                throw new Exception("Property {$property->id} is not geocoded");
            }
        }

        if ($properties->count() < 2) {
            throw new Exception('At least 2 properties are required for route optimization');
        }

        // Build waypoints for Route Optimization API
        $waypoints = [];
        foreach ($properties as $property) {
            $waypoints[] = [
                'address' => $property->full_address,
                'location' => [
                    'latLng' => [
                        'latitude' => (float) $property->latitude,
                        'longitude' => (float) $property->longitude,
                    ],
                ],
            ];
        }

        // Prepare request payload for Google Routes API
        $payload = [
            'routingPreference' => 'TRAFFIC_AWARE',
            'computeBestOrder' => true,
            'routeModifiers' => [
                'avoidTolls' => false,
                'avoidHighways' => false,
                'avoidFerries' => false,
            ],
            'waypoints' => $waypoints,
        ];

        if ($startLocation) {
            $payload['origin'] = ['address' => $startLocation];
            $payload['destination'] = ['address' => $startLocation];
        } else {
            // Use first property as both origin and destination (round trip)
            $payload['origin'] = $waypoints[0];
            $payload['destination'] = $waypoints[0];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters,routes.optimizedIntermediateWaypointIndex,routes.legs',
        ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', $payload);

        if (! $response->successful()) {
            throw new Exception('Routes API request failed: '.$response->status().' - '.$response->body());
        }

        $data = $response->json();

        if (empty($data['routes'])) {
            throw new Exception('No routes returned from API');
        }

        $route = $data['routes'][0];
        $optimizedOrder = $route['optimizedIntermediateWaypointIndex'] ?? [];

        // Map optimized order back to Property models
        $optimizedProperties = [];
        foreach ($optimizedOrder as $index) {
            $optimizedProperties[] = $properties->values()[$index];
        }

        return [
            'optimized_order' => $optimizedProperties,
            'total_distance_meters' => $route['distanceMeters'] ?? 0,
            'total_duration_seconds' => $route['duration'] ? (int) rtrim($route['duration'], 's') : 0,
            'legs' => $route['legs'] ?? [],
        ];
    }

    public function optimizeForDate(Carbon $date, ?string $startLocation = null): array
    {
        $appointments = ServiceAppointment::query()
            ->readyForRouting()
            ->forDate($date)
            ->with(['property.customer', 'serviceType'])
            ->get();

        if ($appointments->isEmpty()) {
            throw new Exception('No scheduled appointments found for this date with geocoded properties');
        }

        $properties = $appointments->pluck('property');

        $result = $this->optimize($properties, $startLocation);

        $result['appointments'] = $appointments;
        $result['appointment_count'] = $appointments->count();

        return $result;
    }

    public function optimizeForTeam($team, Carbon $date, ?string $startLocation = null): array
    {
        $teamId = is_object($team) ? $team->id : $team;

        $appointments = ServiceAppointment::query()
            ->readyForRouting()
            ->assignedToTeam($teamId)
            ->forDate($date)
            ->with(['property.customer', 'serviceType', 'team'])
            ->get();

        if ($appointments->isEmpty()) {
            throw new Exception('No scheduled appointments found for this team and date with geocoded properties');
        }

        $properties = $appointments->pluck('property');

        $result = $this->optimize($properties, $startLocation);

        // Update route order for appointments
        foreach ($result['optimized_order'] as $index => $property) {
            $appointment = $appointments->firstWhere('property_id', $property->id);
            if ($appointment) {
                $appointment->update(['route_order' => $index + 1]);
            }
        }

        $result['appointments'] = $appointments->sortBy('route_order');
        $result['appointment_count'] = $appointments->count();
        $result['team_id'] = $teamId;

        return $result;
    }

    public function optimizeAllTeamsForDate(Carbon $date, ?string $startLocation = null): array
    {
        $teams = \App\Models\Team::active()
            ->whereHas('appointments', function ($query) use ($date) {
                $query->whereDate('scheduled_date', $date)
                    ->readyForRouting();
            })
            ->get();

        if ($teams->isEmpty()) {
            throw new Exception('No teams with appointments found for this date');
        }

        $results = [];
        $totalOptimized = 0;
        $errors = [];

        foreach ($teams as $team) {
            try {
                $result = $this->optimizeForTeam($team, $date, $startLocation);
                $results[] = [
                    'team' => $team,
                    'result' => $result,
                ];
                $totalOptimized += $result['appointment_count'];
            } catch (Exception $e) {
                $errors[] = [
                    'team' => $team->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'teams_optimized' => count($results),
            'total_appointments' => $totalOptimized,
            'results' => $results,
            'errors' => $errors,
        ];
    }
}
