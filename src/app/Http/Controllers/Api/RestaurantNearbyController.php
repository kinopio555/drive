<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantNearbyRequest;
use App\Models\RestaurantNearby;
use Illuminate\Http\JsonResponse;

class RestaurantNearbyController extends Controller
{
    public function store(StoreRestaurantNearbyRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $record = RestaurantNearby::create([
            'user_id' => $user->id,
            'origin' => $request->input('origin'),
            'destination' => $request->input('destination'),
            'restaurants_names' => $request->input('restaurants_names'),
        ]);

        return response()->json($record, 201);
    }
}
