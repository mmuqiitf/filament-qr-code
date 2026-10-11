<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Support\QrRenderSpec;

class QrImageController
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'data' => ['required', 'string', 'max:2000'],
            'size' => ['sometimes', 'integer', 'min:50', 'max:1000'],
            'margin' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'format' => ['sometimes', 'string', 'in:svg,png'],
            'foreground' => ['sometimes', 'string', 'max:7'],
            'background' => ['sometimes', 'string', 'max:7'],
            'ec' => ['sometimes', 'nullable', 'string', 'in:L,M,Q,H'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'logoSize' => ['sometimes', 'integer', 'min:10', 'max:200'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $spec = QrRenderSpec::fromSignedParams($validated);

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
                'Cache-Control' => 'public, max-age=86400, immutable',
            ]
        );
    }
}
