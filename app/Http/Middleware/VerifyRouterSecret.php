<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRouterSecret
{
    /**
     * Handle an incoming request.
     * Verifies that the request includes the correct router callback secret.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.router_callback_secret');

        if (empty($secret)) {
            Log::error('VerifyRouterSecret: ROUTER_CALLBACK_SECRET is not configured. Blocking request.');
            return response()->json(['status' => 'error', 'message' => 'Server misconfiguration'], 500);
        }

        $provided = $request->header('X-Router-Secret') ?? $request->input('secret');

        if (!hash_equals($secret, (string) $provided)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
