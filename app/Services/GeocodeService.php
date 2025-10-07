<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeocodeService
{
    public function geocode(string $address): array
    {
        $apiKey = config('services.google.api_key');

        if (empty($apiKey)) {
            throw new Exception('Google API key is not configured');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            throw new Exception('Geocoding API request failed: '.$response->status());
        }

        $data = $response->json();

        if ($data['status'] === 'ZERO_RESULTS') {
            throw new Exception('No results found for the provided address');
        }

        if ($data['status'] === 'OVER_QUERY_LIMIT') {
            throw new Exception('Google API query limit exceeded');
        }

        if ($data['status'] === 'REQUEST_DENIED') {
            throw new Exception('Google API request denied: '.($data['error_message'] ?? 'Unknown error'));
        }

        if ($data['status'] === 'INVALID_REQUEST') {
            throw new Exception('Invalid geocoding request');
        }

        if ($data['status'] !== 'OK' || empty($data['results'])) {
            throw new Exception('Geocoding failed: '.$data['status']);
        }

        $location = $data['results'][0]['geometry']['location'];

        return [
            'lat' => $location['lat'],
            'lng' => $location['lng'],
        ];
    }
}
