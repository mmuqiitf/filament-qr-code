<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

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
        ]);

        $format = QrFormat::tryFrom(strtolower($validated['format'] ?? 'svg')) ?? QrFormat::Svg;

        $service = QrCodeService::make()
            ->size((int) ($validated['size'] ?? 300))
            ->margin((int) ($validated['margin'] ?? 2))
            ->color((string) ($validated['foreground'] ?? '#000000'))
            ->backgroundColor((string) ($validated['background'] ?? '#ffffff'))
            ->format($format);

        if (! empty($validated['ec'])) {
            $service->errorCorrection((string) $validated['ec']);
        }

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
