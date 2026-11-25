<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Printing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Short-lived cache of a rendered print document, keyed by an unguessable token.
 *
 * @internal
 */
final class LabelSheetStore
{
    public const KEY_PREFIX = 'filament-qr-code.labels.';

    public const TTL_MINUTES = 30;

    public static function put(string $html): string
    {
        $token = Str::lower(Str::random(40));

        Cache::put(self::KEY_PREFIX.$token, $html, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    public static function get(string $token): ?string
    {
        if (preg_match('/^[a-z0-9]+$/', $token) !== 1) {
            return null;
        }

        $html = Cache::get(self::KEY_PREFIX.$token);

        return is_string($html) && $html !== '' ? $html : null;
    }
}
