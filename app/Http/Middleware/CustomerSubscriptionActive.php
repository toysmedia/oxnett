<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerSubscriptionActive
{
    /**
     * Routes that expired subscribers are still allowed to access.
     */
    private const ALLOWED_PATHS = [
        'customer/payments/renew',
        'customer/logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\Subscriber|null $subscriber */
        $subscriber = auth('customer')->user();

        if (!$subscriber) {
            return $next($request);
        }

        if ($subscriber->isExpired() || $subscriber->status === 'suspended') {
            $path = ltrim($request->path(), '/');

            foreach (self::ALLOWED_PATHS as $allowed) {
                if (str_starts_with($path, $allowed)) {
                    return $next($request);
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your subscription has expired. Please renew to continue.',
                ], 403);
            }

            return redirect()->route('customer.payments.renew')
                ->with('warning', 'Your subscription has expired. Please renew to continue.');
        }

        return $next($request);
    }
}
