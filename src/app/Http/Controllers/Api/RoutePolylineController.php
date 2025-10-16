<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoutePolylineRequest;
use App\Services\GoogleRoutesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class RoutePolylineController extends Controller
{
    public function __construct(private readonly GoogleRoutesService $googleRoutesService)
    {
    }

    public function __invoke(RoutePolylineRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = $request->user();
        $identifier = $user?->getAuthIdentifier();
        $limiterKey = $identifier
            ? "route-polyline:user:{$identifier}"
            : 'route-polyline:ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 2)) {
            return response()->json([
                'message' => '1分あたり2リクエストまでしかできません',
            ], 429);
        }

        RateLimiter::hit($limiterKey, 60);

        try {
            $route = $this->googleRoutesService->fetchRouteData(
                $payload['origin'],
                $payload['destination'],
                $identifier,
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to fetch route polyline.',
            ], 500);
        }

        return response()->json($route);
    }
}
