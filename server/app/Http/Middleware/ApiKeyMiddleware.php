<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request from Paymenter / external API.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing API Key. Provide via Bearer token or X-API-Key header.',
            ], 401);
        }

        $apiKey = ApiKey::verify($token);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or disabled API Key.',
            ], 401);
        }

        // Attach API Key to request attributes for downstream use
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
