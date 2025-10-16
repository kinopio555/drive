<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantNearbyRequest;
use App\Models\RestaurantNearby;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantNearbyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $records = RestaurantNearby::query()
            ->where('user_id', $user->id)
            ->get(['id', 'origin', 'destination', 'restaurants_names']);

        return response()->json($records);
    }

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

    public function destroy(Request $request, RestaurantNearby $restaurantNearby): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($restaurantNearby->user_id !== $user->id) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $restaurantNearby->delete();

        return response()->json(null, 204);
    }
}
