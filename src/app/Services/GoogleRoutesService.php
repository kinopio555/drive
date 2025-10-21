<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class GoogleRoutesService
{
    private const SAMPLE_INTERVAL_METERS = 200.0;
    private const NEARBY_SEARCH_RADIUS_METERS = 100;
    private const MAX_SAMPLE_POINTS = 500;

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.google_maps.api_key');
    }

    public function fetchRouteData(string $originPlaceName, string $destinationPlaceName, ?string $userIdentifier = null): array
    {
        $origin = $this->geocodePlaceName($originPlaceName);
        $destination = $this->geocodePlaceName($destinationPlaceName);

        $polyline = $this->requestRoutePolyline($origin, $destination);
        $decodedPoints = $this->decodePolyline($polyline);

        if (count($decodedPoints) < 2) {
            throw new RuntimeException('Routes API response missing sufficient polyline points.');
        }

        $sampledPoints = $this->samplePolylinePoints($decodedPoints, self::SAMPLE_INTERVAL_METERS);

        if (count($sampledPoints) > self::MAX_SAMPLE_POINTS) {
            throw new RuntimeException('Route too long to sample safely.');
        }

        $samples = [];
        $restaurants_names = [];

        foreach ($sampledPoints as $point) {
            $restaurants = $this->fetchNearbyRestaurants($point, $userIdentifier);

            $samples[] = [
                'coordinate' => [
                    'latitude' => $point['latitude'],
                    'longitude' => $point['longitude'],
                ],
                'restaurants' => $restaurants,
            ];

            foreach ($restaurants as $restaurant) {
                array_push($restaurants_names, $restaurant['name']);
            }
        }

        return [
            'polyline' => $polyline,
            'samples' => $samples,
            'restaurants_names' => $restaurants_names
        ];
    }

    private function geocodePlaceName(string $placeName): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Google Maps API key is not configured.');
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $this->apiKey,
            'X-Goog-FieldMask' => 'places.location',
        ])->post('https://places.googleapis.com/v1/places:searchText', [
            'textQuery' => $placeName,
            'languageCode' => 'ja',
        ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Places text search request failed.', 0, $exception);
        }

        $payload = $response->json();
        $places = $payload['places'] ?? [];

        if (count($places) === 0) {
            throw new RuntimeException("Places API returned zero results for '{$placeName}'.");
        }

        $location = data_get($places, '0.location');

        if (! isset($location['latitude'], $location['longitude'])) {
            throw new RuntimeException("Places API response missing coordinates for '{$placeName}'.");
        }

        return [
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
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

    private function decodePolyline(string $polyline): array
    {
        if ($polyline === '') {
            throw new RuntimeException('Routes API response missing polyline.');
        }

        $coordinates = [];
        $index = 0;
        $lat = 0;
        $lng = 0;
        $length = strlen($polyline);

        while ($index < $length) {
            $result = 0;
            $shift = 0;

            do {
                $char = ord($polyline[$index++]) - 63;
                $result |= ($char & 0x1f) << $shift;
                $shift += 5;
            } while ($char >= 0x20);

            $deltaLat = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $lat += $deltaLat;

            $result = 0;
            $shift = 0;

            do {
                $char = ord($polyline[$index++]) - 63;
                $result |= ($char & 0x1f) << $shift;
                $shift += 5;
            } while ($char >= 0x20);

            $deltaLng = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $lng += $deltaLng;

            $coordinates[] = [
                'latitude' => $lat / 1e5,
                'longitude' => $lng / 1e5,
            ];
        }

        return $coordinates;
    }

    private function samplePolylinePoints(array $points, float $intervalMeters): array
    {
        if (count($points) <= 1) {
            return $points;
        }

        $samples = [$points[0]];
        $distanceSinceLastSample = 0.0;

        for ($i = 0; $i < count($points) - 1; $i++) {
            $segmentStart = $points[$i];
            $segmentEnd = $points[$i + 1];
            $segmentLength = $this->haversineDistanceMeters($segmentStart, $segmentEnd);

            if ($segmentLength === 0.0) {
                continue;
            }

            $currentPosition = $segmentStart;
            $remainingLength = $segmentLength;

            while ($distanceSinceLastSample + $remainingLength >= $intervalMeters) {
                $distanceNeeded = $intervalMeters - $distanceSinceLastSample;
                $ratio = $distanceNeeded / $remainingLength;
                $newPoint = $this->interpolatePoint($currentPosition, $segmentEnd, $ratio);

                if (count($samples) >= self::MAX_SAMPLE_POINTS) {
                    throw new RuntimeException('Route too long to sample safely.');
                }

                $samples[] = $newPoint;
                $currentPosition = $newPoint;
                $remainingLength = $this->haversineDistanceMeters($currentPosition, $segmentEnd);
                $distanceSinceLastSample = 0.0;

                if ($remainingLength === 0.0) {
                    break;
                }
            }

            $distanceSinceLastSample += $remainingLength;
        }

        $lastPoint = $points[array_key_last($points)];
        $lastSample = $samples[array_key_last($samples)];

        if ($this->haversineDistanceMeters($lastSample, $lastPoint) > 0.01) {
            $samples[] = $lastPoint;
        }

        return $this->uniqueSamples($samples);
    }

    private function uniqueSamples(array $samples): array
    {
        $unique = [];
        $seen = [];

        foreach ($samples as $point) {
            $key = sprintf('%.6f:%.6f', $point['latitude'], $point['longitude']);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $point;
        }

        return $unique;
    }

    private function haversineDistanceMeters(array $start, array $end): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($start['latitude']);
        $lngFrom = deg2rad($start['longitude']);
        $latTo = deg2rad($end['latitude']);
        $lngTo = deg2rad($end['longitude']);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));

        return $earthRadius * $angle;
    }

    private function interpolatePoint(array $start, array $end, float $ratio): array
    {
        $ratio = max(0.0, min(1.0, $ratio));

        return [
            'latitude' => $start['latitude'] + ($end['latitude'] - $start['latitude']) * $ratio,
            'longitude' => $start['longitude'] + ($end['longitude'] - $start['longitude']) * $ratio,
        ];
    }

    private function fetchNearbyRestaurants(array $point, ?string $userIdentifier = null): array
    {
        if ($userIdentifier !== null) {
            $limiterKey = "nearby-restaurants:user:{$userIdentifier}";

            if (RateLimiter::tooManyAttempts($limiterKey, 30)) {
                throw new RuntimeException('1分間に検索できる店舗数は30件までです');
            }

            RateLimiter::hit($limiterKey, 60);
        }

        if ($this->apiKey === '') {
            throw new RuntimeException('Google Maps API key is not configured.');
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $this->apiKey,
            'X-Goog-FieldMask' => implode(',', [
                'places.id',
                'places.displayName',
                'places.rating',
                'places.userRatingCount',
                'places.location',
                'places.formattedAddress',
            ]),
        ])->post('https://places.googleapis.com/v1/places:searchNearby', [
            'includedTypes' => ['restaurant'],
            'languageCode' => 'ja',
            'locationRestriction' => [
                'circle' => [
                    'center' => [
                        'latitude' => $point['latitude'],
                        'longitude' => $point['longitude'],
                    ],
                    'radius' => self::NEARBY_SEARCH_RADIUS_METERS,
                ],
            ],
        ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Google Places API request failed.', 0, $exception);
        }

        $payload = $response->json();
        $places = $payload['places'] ?? [];

        $restaurants = [];

        foreach ($places as $place) {
            $placeId = $place['id'] ?? null;

            if (! $placeId) {
                continue;
            }

            $restaurants[$placeId] = [
                'place_id' => $placeId,
                'name' => data_get($place, 'displayName.text'),
                'rating' => $place['rating'] ?? null,
                'user_ratings_total' => data_get($place, 'userRatingCount'),
                'location' => [
                    'latitude' => data_get($place, 'location.latitude'),
                    'longitude' => data_get($place, 'location.longitude'),
                ],
                'vicinity' => $place['formattedAddress'] ?? null,
            ];
        }

        return array_values($restaurants);
    }
}
