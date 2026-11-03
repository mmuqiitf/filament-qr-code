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
        return 'barcode:'.md5(implode('|', [
            $this->data,
            $this->type->value,
            $this->format->value,
            $this->barWidth,
            $this->height,
            $this->margin,
            $this->foregroundColor,
            $this->backgroundColor,
            $this->showText ? '1' : '0',
            $this->fontSize,
            $this->fontPath ?? '',
        ]));
    }
}
