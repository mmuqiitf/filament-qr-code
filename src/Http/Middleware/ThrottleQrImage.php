<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies `qr-code.image_route.throttle` (e.g. '60,1') to the image route.
 * Read per request so the setting can change without re-registering routes.
 *
 * @internal Not covered by semver; may change in any release.
 */
class ThrottleQrImage extends ThrottleRequests
{
    /**
     * @param  mixed  ...$parameters
     */
    public function handle($request, Closure $next, ...$parameters): Response
    {
        $throttle = config('qr-code.image_route.throttle');

        if (! is_string($throttle) || trim($throttle) === '') {
            return $next($request);
        }

        $parts = array_map('trim', explode(',', $throttle));

        return parent::handle($request, $next, $parts[0], is_numeric($parts[1] ?? null) ? (float) $parts[1] : 1, 'filament-qr-code-image');
    }
}
