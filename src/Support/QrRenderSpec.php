<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

/**
 * Single source of truth for how a QR code looks.
 *
 * QR Code Display, QR Entry, the table column (thumbnail, eager modal, and
 * lazy signed preview), and both download actions used to assemble render
 * options independently: the signed preview silently dropped the logo and
 * caption, and downloads hardcoded black on white. Every surface now builds
 * one spec, so render drift is impossible by construction.
 */
final class QrRenderSpec
{
    public function __construct(
        public readonly int $size = 200,
        public readonly int $margin = 2,
        public readonly string $foreground = '#000000',
        public readonly string $background = '#ffffff',
        public readonly QrFormat|string|null $format = null,
        public readonly ?string $errorCorrection = null,
        public readonly ?string $logoPath = null,
        public readonly int $logoSize = 50,
        public readonly ?string $caption = null,
    ) {}

    public function toService(): QrCodeService
    {
        $service = QrCodeService::make()
            ->size($this->size)
            ->margin($this->margin)
            ->color($this->foreground)
            ->backgroundColor($this->background);

        if ($this->format instanceof QrFormat || is_string($this->format)) {
            $service->format($this->format);
        }

        if ($this->errorCorrection !== null && $this->errorCorrection !== '') {
            $service->errorCorrection($this->errorCorrection);
        }

        if ($this->logoPath !== null && $this->logoPath !== '') {
            $service->logo($this->logoPath, $this->logoSize);
        }

        if ($this->caption !== null && $this->caption !== '') {
            $service->withText($this->caption);
        }

        return $service;
    }

    public function toDataUri(string $data): string
    {
        if ($data === '') {
            return '';
        }

        return $this->toService()->generate($data)->toDataUri();
    }

    public function formatValue(): ?string
    {
        if ($this->format instanceof QrFormat) {
            return $this->format->value;
        }

        if (is_string($this->format) && $this->format !== '') {
            return strtolower($this->format) === 'png' ? 'png' : 'svg';
        }

        return null;
    }

    /**
     * Signed-preview params: every option the controller needs to reproduce
     * this exact render, including logo and caption.
     *
     * @return array<string, mixed>
     */
    public function toSignedParams(string $data): array
    {
        return array_filter([
            'data' => $data,
            'size' => $this->size,
            'margin' => $this->margin,
            'format' => $this->formatValue(),
            'foreground' => $this->foreground,
            'background' => $this->background,
            'ec' => $this->errorCorrection,
            'logo' => $this->logoPath,
            'logoSize' => $this->logoPath !== null && $this->logoPath !== '' ? $this->logoSize : null,
            'caption' => $this->caption,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromSignedParams(array $validated): self
    {
        $logo = isset($validated['logo']) && is_string($validated['logo']) && $validated['logo'] !== ''
            ? $validated['logo']
            : null;

        $caption = isset($validated['caption']) && is_string($validated['caption']) && $validated['caption'] !== ''
            ? $validated['caption']
            : null;

        $errorCorrection = isset($validated['ec']) && is_string($validated['ec']) && $validated['ec'] !== ''
            ? $validated['ec']
            : null;

        return new self(
            size: isset($validated['size']) && is_numeric($validated['size']) ? (int) $validated['size'] : 300,
            margin: isset($validated['margin']) && is_numeric($validated['margin']) ? (int) $validated['margin'] : 2,
            foreground: isset($validated['foreground']) && is_string($validated['foreground']) ? $validated['foreground'] : '#000000',
            background: isset($validated['background']) && is_string($validated['background']) ? $validated['background'] : '#ffffff',
            format: isset($validated['format']) && is_string($validated['format']) ? $validated['format'] : null,
            errorCorrection: $errorCorrection,
            logoPath: $logo,
            logoSize: isset($validated['logoSize']) && is_numeric($validated['logoSize']) ? (int) $validated['logoSize'] : 50,
            caption: $caption,
        );
    }
}
