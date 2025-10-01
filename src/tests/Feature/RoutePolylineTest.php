<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoutePolylineTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_route_polyline_endpoint(): void
    {
        $response = $this->postJson('/api/routes/polyline', [
            'origin' => '東京駅',
            'destination' => '京都駅',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_receives_polyline(): void
    {
        Config::set('services.google_maps.api_key', 'test-key');

        $geocodeResponse = static fn (float $lat, float $lng) => [
            'status' => 'OK',
            'results' => [
                [
                    'geometry' => [
                        'location' => [
                            'lat' => $lat,
                            'lng' => $lng,
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/*' => Http::sequence()
                ->push($geocodeResponse(35.681236, 139.767125), 200)
                ->push($geocodeResponse(34.985849, 135.758766), 200),
            'https://routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [
                    [
                        'polyline' => [
                            'encodedPolyline' => 'w~wxEqgatYcBcBcBcB',
                        ],
                    ],
                ],
            ], 200),
            'https://maps.googleapis.com/maps/api/place/nearbysearch/json*' => Http::sequence()
                ->push($this->placesResponse('place-alpha', 'Restaurant Alpha'), 200)
                ->push($this->placesResponse('place-beta', 'Restaurant Beta'), 200)
                ->push($this->placesResponse('place-gamma', 'Restaurant Gamma'), 200)
                ->push($this->placesResponse('place-delta', 'Restaurant Delta'), 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/routes/polyline', [
            'origin' => '東京駅',
            'destination' => '京都駅',
        ]);

        $response->assertOk()
            ->assertJson([
                'polyline' => 'w~wxEqgatYcBcBcBcB',
            ])
            ->assertJsonStructure([
                'polyline',
                'samples' => [
                    [
                        'coordinate' => ['latitude', 'longitude'],
                        'restaurants',
                    ],
                ],
            ]);

        $samples = $response->json('samples');

        $this->assertCount(4, $samples);
        $this->assertSame('Restaurant Alpha', $samples[0]['restaurants'][0]['name']);

        Http::assertSentCount(7);
    }

    private function placesResponse(string $id, string $name): array
    {
        return [
            'status' => 'OK',
            'results' => [
                [
                    'place_id' => $id,
                    'name' => $name,
                    'geometry' => [
                        'location' => [
                            'lat' => 35.0,
                            'lng' => 139.0,
                        ],
                    ],
                    'vicinity' => 'Test Vicinity',
                ],
            ],
        ];
    }
}
