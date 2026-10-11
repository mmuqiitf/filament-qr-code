<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Mmuqiitf\FilamentQrCode\Support\QrRenderSpec;

trait HasQrRendering
{
    protected function buildQrCodeService(
        int $size,
        int $margin,
        string $foreground,
        string $background,
        QrFormat|string|null $format = null,
        ?string $errorCorrection = null,
        ?string $logoPath = null,
        int $logoSize = 50,
        ?string $caption = null,
    ): QrCodeService {
        return (new QrRenderSpec(
            size: $size,
            margin: $margin,
            foreground: $foreground,
            background: $background,
            format: $format,
            errorCorrection: $errorCorrection,
            logoPath: $logoPath,
            logoSize: $logoSize,
            caption: $caption,
        ))->toService();
    }

    protected function renderQrDataUri(
        string $data,
        int $size,
        int $margin,
        string $foreground,
        string $background,
        QrFormat|string|null $format = null,
        ?string $errorCorrection = null,
        ?string $logoPath = null,
        int $logoSize = 50,
        ?string $caption = null,
    ): string {
        if ($data === '') {
            return '';
        }

        return $this->buildQrCodeService(
            size: $size,
            margin: $margin,
            foreground: $foreground,
            background: $background,
            format: $format,
            errorCorrection: $errorCorrection,
            logoPath: $logoPath,
            logoSize: $logoSize,
            caption: $caption,
        )->generate($data)->toDataUri();
    }
}
