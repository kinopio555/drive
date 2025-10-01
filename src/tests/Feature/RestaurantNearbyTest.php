<?php

namespace Tests\Feature;

use App\Models\RestaurantNearby;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestaurantNearbyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_restaurant_nearby_record(): void
    {
        $payload = [
            'origin' => 'Tokyo Station',
            'destination' => 'Kyoto Station',
            'restaurants_names' => ['Alpha', 'Beta'],
        ];

        $response = $this->postJson('/api/restaurants-nearby', $payload);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('restaurants_nearby', 0);
    }

    public function test_authenticated_user_can_create_restaurant_nearby_record(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $payload = [
            'origin' => 'Tokyo Station',
            'destination' => 'Kyoto Station',
            'restaurants_names' => ['Alpha', 'Beta'],
        ];

        $response = $this->postJson('/api/restaurants-nearby', $payload);

        $response->assertCreated()
            ->assertJson([
                'user_id' => $user->id,
                'origin' => $payload['origin'],
                'destination' => $payload['destination'],
                'restaurants_names' => $payload['restaurants_names'],
            ]);

        $this->assertDatabaseHas('restaurants_nearby', [
            'user_id' => $user->id,
            'origin' => $payload['origin'],
            'destination' => $payload['destination'],
        ]);

        $record = RestaurantNearby::first();
        $this->assertSame($payload['restaurants_names'], $record->restaurants_names);
    }
}
