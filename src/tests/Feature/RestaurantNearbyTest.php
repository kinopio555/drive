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

    public function test_guests_cannot_view_restaurant_nearby_records(): void
    {
        $response = $this->getJson('/api/restaurants-nearby');

        $response->assertUnauthorized();
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

    public function test_authenticated_user_receives_only_their_restaurant_nearby_records(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $otherUser = User::factory()->create();

        RestaurantNearby::create([
            'user_id' => $user->id,
            'origin' => 'Tokyo Station',
            'destination' => 'Kyoto Station',
            'restaurants_names' => ['Alpha', 'Beta'],
        ]);

        RestaurantNearby::create([
            'user_id' => $user->id,
            'origin' => 'Osaka Station',
            'destination' => 'Nagoya Station',
            'restaurants_names' => ['Gamma'],
        ]);

        RestaurantNearby::create([
            'user_id' => $otherUser->id,
            'origin' => 'Sapporo Station',
            'destination' => 'Hakodate Station',
            'restaurants_names' => ['Delta'],
        ]);

        $response = $this->getJson('/api/restaurants-nearby');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJson(
                [
                    [
                        'origin' => 'Tokyo Station',
                        'destination' => 'Kyoto Station',
                        'restaurants_names' => ['Alpha', 'Beta'],
                    ],
                    [
                        'origin' => 'Osaka Station',
                        'destination' => 'Nagoya Station',
                        'restaurants_names' => ['Gamma'],
                    ],
                ]
            )
            ->assertJsonMissing([
                'restaurants_names' => ['Delta'],
            ]);
    }

    public function test_guests_cannot_delete_restaurant_nearby_record(): void
    {
        $record = RestaurantNearby::create([
            'user_id' => User::factory()->create()->id,
            'origin' => 'Tokyo Station',
            'destination' => 'Kyoto Station',
            'restaurants_names' => ['Alpha', 'Beta'],
        ]);

        $response = $this->deleteJson("/api/restaurants-nearby/{$record->id}");

        $response->assertUnauthorized();
        $this->assertDatabaseHas('restaurants_nearby', [
            'id' => $record->id,
        ]);
    }

    public function test_authenticated_user_can_delete_their_restaurant_nearby_record(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $record = RestaurantNearby::create([
            'user_id' => $user->id,
            'origin' => 'Tokyo Station',
            'destination' => 'Kyoto Station',
            'restaurants_names' => ['Alpha', 'Beta'],
        ]);

        $response = $this->deleteJson("/api/restaurants-nearby/{$record->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('restaurants_nearby', [
            'id' => $record->id,
        ]);
    }

    public function test_authenticated_user_cannot_delete_other_users_restaurant_nearby_record(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $otherUser = User::factory()->create();

        $record = RestaurantNearby::create([
            'user_id' => $otherUser->id,
            'origin' => 'Osaka Station',
            'destination' => 'Nagoya Station',
            'restaurants_names' => ['Gamma'],
        ]);

        $response = $this->deleteJson("/api/restaurants-nearby/{$record->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('restaurants_nearby', [
            'id' => $record->id,
        ]);
    }
}
