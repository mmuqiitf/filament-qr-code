<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use BaconQrCode\Common\ErrorCorrectionLevel;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

/**
 * Every input that determines a QR render. Two equal option sets always
 * produce identical bytes, which is what makes the render cache sound.
 *
 * @internal Not an extension point; may change without notice. Use QrCodeService.
 */
final readonly class QrRenderOptions
{
    public function __construct(
        public string $data,
        public int $size,
        public int $margin,
        public QrFormat $format,
        public string $foregroundColor,
        public string $backgroundColor,
        public ErrorCorrectionLevel $errorCorrectionLevel,
        public ?string $overlayText = null,
        public int $fontSize = 16,
        public string $fontColor = '#000000',
        public ?string $fontPath = null,
        public ?string $logoPath = null,
        public int $logoSize = 50,
    ) {}

    public function hasCaption(): bool
    {
        return $this->overlayText !== null && $this->overlayText !== '';
    }

    public function hasLogoFile(): bool
    {
        return $this->logoPath !== null && file_exists($this->logoPath);
    }

    public function cacheKey(): string
    {
        return RenderCacheKey::make('qr', [
            'data' => $this->data,
            'size' => $this->size,
            'margin' => $this->margin,
            'format' => $this->format->value,
            'foreground' => $this->foregroundColor,
            'background' => $this->backgroundColor,
            'ec' => $this->errorCorrectionLevel->getBits(),
            'overlay' => $this->overlayText ?? '',
            'fontSize' => $this->fontSize,
            'fontColor' => $this->fontColor,
            'font' => RenderCacheKey::fileToken($this->fontPath),
            'logo' => RenderCacheKey::fileToken($this->logoPath),
            'logoSize' => $this->logoSize,
        ]);
    }
}
