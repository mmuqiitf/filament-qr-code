<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Support\LogoPathGuard;
use Mmuqiitf\FilamentQrCode\Support\QrRenderSpec;

class QrImageController
{
    public function __invoke(Request $request): Response
    {
        // Invalid input is always a 422: an <img> tag cannot follow a
        // validation redirect, and a bad colour must not surface as a 500.
        $validator = Validator::make($request->query(), [
            'data' => ['required', 'string', 'max:2000'],
            'size' => ['sometimes', 'integer', 'min:50', 'max:1000'],
            'margin' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'format' => ['sometimes', 'string', 'in:svg,png'],
            'foreground' => ['sometimes', 'string', 'max:7', 'regex:/^#*([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'background' => ['sometimes', 'string', 'max:7', 'regex:/^#*([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'ec' => ['sometimes', 'nullable', 'string', 'in:L,M,Q,H'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'logoSize' => ['sometimes', 'integer', 'min:10', 'max:200'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            abort(422, (string) $validator->errors()->first());
        }

        $validated = $validator->validated();

        // Opt-in: only the canonical #rrggbb form.
        if ((bool) config('qr-code.image_route.strict_colors', false)) {
            foreach (['foreground', 'background'] as $key) {
                if (isset($validated[$key]) && ! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $validated[$key])) {
                    abort(422, "The {$key} colour must be #rrggbb.");
                }
            }
        }

        $spec = QrRenderSpec::fromSignedParams($validated);

        if ($spec->logoPath !== null && $spec->logoPath !== '' && ! LogoPathGuard::allows($spec->logoPath)) {
            abort(422, 'The logo is outside the configured logo base paths.');
        }

        // A logo or caption forces PNG rasterization inside the service,
        // matching the inline data-URI renders exactly.
        $format = ($spec->logoPath !== null && $spec->logoPath !== '') || ($spec->caption !== null && $spec->caption !== '')
            ? QrFormat::Png
            : (QrFormat::tryFrom($spec->formatValue() ?? 'svg') ?? QrFormat::Svg);

        $service = $spec->toService();
        $service->generate((string) $validated['data']);

        return new Response(
            $service->getRaw(),
            200,
            [
                'Content-Type' => $format->getMimeType(),
                'Cache-Control' => 'public, max-age='.self::maxAge().', immutable',
            ]
        );
    }

    private static function maxAge(): int
    {
        $ttl = config('qr-code.image_route.ttl');

        return is_numeric($ttl) && (int) $ttl > 0 ? min(86400, (int) $ttl * 60) : 86400;
    }
}
