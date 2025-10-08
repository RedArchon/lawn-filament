<?php

namespace App\Services;

use App\Models\ServiceAppointment;
use App\Models\Team;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class TeamAssignmentService
{
    public function autoAssignAppointments(Carbon $date): array
    {
        $unassignedAppointments = ServiceAppointment::query()
            ->whereDate('scheduled_date', $date)
            ->whereNull('team_id')
            ->where('status', 'scheduled')
            ->with(['property.customer'])
            ->get();

        if ($unassignedAppointments->isEmpty()) {
            throw new Exception('No unassigned appointments found for this date');
        }

        $activeTeams = Team::active()->get();

        if ($activeTeams->isEmpty()) {
            throw new Exception('No active teams available for assignment');
        }

        // Get current appointment counts for each team on this date
        $teamCapacities = $this->calculateTeamCapacities($activeTeams, $date);

        // Sort by available capacity (most available first)
        $teamCapacities = $teamCapacities->sortByDesc('available');

        // Group appointments by geographic proximity if geocoded
        $appointmentGroups = $this->groupAppointmentsByProximity($unassignedAppointments);

        $assignments = [];
        $assignmentCount = 0;

        foreach ($appointmentGroups as $group) {
            // Find team with most available capacity
            $team = $teamCapacities->first(function ($capacity) {
                return $capacity['available'] > 0;
            });

            if (! $team) {
                break; // No more capacity available
            }

            // Assign all appointments in this group to the same team
            foreach ($group as $appointment) {
                $appointment->update(['team_id' => $team['team']->id]);
                $assignmentCount++;

                // Decrease available capacity
                $teamCapacities = $teamCapacities->map(function ($capacity) use ($team) {
                    if ($capacity['team']->id === $team['team']->id) {
                        $capacity['available']--;
                    }

                    return $capacity;
                })->sortByDesc('available')->values();
            }

            $assignments[] = [
                'team' => $team['team']->name,
                'appointments' => $group->count(),
            ];
        }

        return [
            'total_assigned' => $assignmentCount,
            'total_unassigned' => $unassignedAppointments->count(),
            'remaining_unassigned' => $unassignedAppointments->count() - $assignmentCount,
            'assignments' => $assignments,
            'teams_used' => collect($assignments)->pluck('team')->unique()->count(),
        ];
    }

    protected function calculateTeamCapacities(Collection $teams, Carbon $date): Collection
    {
        return $teams->map(function (Team $team) use ($date) {
            $currentCount = $team->appointments()
                ->whereDate('scheduled_date', $date)
                ->count();

            $maxCapacity = $team->max_daily_appointments ?? 999;
            $available = max(0, $maxCapacity - $currentCount);

            return [
                'team' => $team,
                'current' => $currentCount,
                'max' => $maxCapacity,
                'available' => $available,
            ];
        });
    }

    protected function groupAppointmentsByProximity(Collection $appointments): Collection
    {
        // Separate geocoded and non-geocoded appointments
        $geocoded = $appointments->filter(function ($appointment) {
            return $appointment->property->latitude
                && $appointment->property->longitude
                && ! $appointment->property->geocoding_failed;
        });

        $notGeocoded = $appointments->filter(function ($appointment) {
            return ! $appointment->property->latitude
                || ! $appointment->property->longitude
                || $appointment->property->geocoding_failed;
        });

        $groups = collect();

        // For geocoded appointments, create clusters
        if ($geocoded->isNotEmpty()) {
            $clusters = $this->clusterByProximity($geocoded);
            foreach ($clusters as $cluster) {
                $groups->push($cluster);
            }
        }

        // Add non-geocoded appointments as individual groups
        foreach ($notGeocoded as $appointment) {
            $groups->push(collect([$appointment]));
        }

        return $groups;
    }

    protected function clusterByProximity(Collection $appointments, float $maxDistanceKm = 5.0): array
    {
        $clusters = [];
        $remaining = $appointments->values()->all();

        while (! empty($remaining)) {
            $seed = array_shift($remaining);
            $cluster = [$seed];

            $i = 0;
            while ($i < count($remaining)) {
                $candidate = $remaining[$i];

                // Check if candidate is close to any appointment in cluster
                $isClose = false;
                foreach ($cluster as $clusterMember) {
                    $distance = $this->calculateDistance(
                        (float) $clusterMember->property->latitude,
                        (float) $clusterMember->property->longitude,
                        (float) $candidate->property->latitude,
                        (float) $candidate->property->longitude
                    );

                    if ($distance <= $maxDistanceKm) {
                        $isClose = true;
                        break;
                    }
                }

                if ($isClose) {
                    $cluster[] = $candidate;
                    array_splice($remaining, $i, 1);
                } else {
                    $i++;
                }
            }

            $clusters[] = collect($cluster);
        }

        return $clusters;
    }

    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
