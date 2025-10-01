<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleRoutesService
{
    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.google_maps.api_key');
    }

    public function fetchPolyline(string $originPlaceName, string $destinationPlaceName): string
    {
        $origin = $this->geocodePlaceName($originPlaceName);
        $destination = $this->geocodePlaceName($destinationPlaceName);

        return $this->requestRoutePolyline($origin, $destination);
    }

    private function geocodePlaceName(string $placeName): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Google Maps API key is not configured.');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $placeName,
            'key' => $this->apiKey,
            'language' => 'ja',
        ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Geocoding request failed.', 0, $exception);
        }

        $payload = $response->json();

        if (($payload['status'] ?? null) !== 'OK') {
            throw new RuntimeException("Geocoding failed for '{$placeName}'.");
        }

        $location = $payload['results'][0]['geometry']['location'] ?? null;

        if (! isset($location['lat'], $location['lng'])) {
            throw new RuntimeException("Geocoding response missing coordinates for '{$placeName}'.");
        }

        return [
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
        ];
    }

    private function requestRoutePolyline(array $origin, array $destination): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Google Maps API key is not configured.');
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $this->apiKey,
            'X-Goog-FieldMask' => 'routes.polyline.encodedPolyline',
        ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
            'origin' => [
                'location' => [
                    'latLng' => $origin,
                ],
            ],
            'destination' => [
                'location' => [
                    'latLng' => $destination,
                ],
            ],
            'travelMode' => 'DRIVE',
        ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Routes API request failed.', 0, $exception);
        }

        $polyline = data_get($response->json(), 'routes.0.polyline.encodedPolyline');

        if (! is_string($polyline) || $polyline === '') {
            throw new RuntimeException('Routes API response missing polyline.');
        }

        return $polyline;
    }
}
