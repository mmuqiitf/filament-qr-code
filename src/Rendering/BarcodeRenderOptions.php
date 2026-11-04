<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

/**
 * Every input that determines a barcode render, so equal option sets
 * always produce identical bytes (see QrRenderCache).
 *
 * @internal Not an extension point; may change without notice. Use BarcodeService.
 */
final readonly class BarcodeRenderOptions
{
    public function __construct(
        public string $data,
        public BarcodeType $type,
        public QrFormat $format,
        public int $barWidth,
        public int $height,
        public int $margin,
        public string $foregroundColor,
        public string $backgroundColor,
        public bool $showText = true,
        public int $fontSize = 14,
        public ?string $fontPath = null,
    ) {}

    public function cacheKey(): string
    {
        return RenderCacheKey::make('barcode', [
            'data' => $this->data,
            'type' => $this->type->value,
            'format' => $this->format->value,
            'barWidth' => $this->barWidth,
            'height' => $this->height,
            'margin' => $this->margin,
            'foreground' => $this->foregroundColor,
            'background' => $this->backgroundColor,
            'showText' => $this->showText,
            'fontSize' => $this->fontSize,
            'font' => RenderCacheKey::fileToken($this->fontPath),
        ]);
    }
}
