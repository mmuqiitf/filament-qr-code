<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeSymbol;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;

/**
 * Every option that determines a barcode render, apart from the value.
 *
 * BarcodeColumn signs these onto filamentQrCode.barcode, and the image
 * controller rebuilds the same service, so the URL and an inline render match.
 *
 * @internal Not covered by semver; may change in any release.
 */
final class BarcodeRenderSpec
{
    public function __construct(
        public readonly BarcodeType $type = BarcodeType::Code128,
        public readonly QrFormat $format = QrFormat::Svg,
        public readonly int $barWidth = 2,
        public readonly int $height = 60,
        public readonly ?int $margin = null,
        public readonly string $foreground = '#000000',
        public readonly string $background = '#ffffff',
        public readonly bool $showText = true,
    ) {}

    public function toService(): BarcodeService
    {
        return BarcodeService::make()
            ->type($this->type)
            ->format($this->format)
            ->barWidth($this->barWidth)
            ->height($this->height)
            ->margin($this->margin)
            ->color($this->foreground)
            ->backgroundColor($this->background)
            ->showText($this->showText);
    }

    /**
     * Human-readable value, check digit included.
     *
     * @throws InvalidBarcodeException
     */
    public function encodedText(string $data): string
    {
        return BarcodeSymbol::encode($this->type, $data)->text;
    }

    /**
     * Query params that reproduce this render. Margin is resolved so the
     * URL does not depend on a later default.
     *
     * @return array<string, int|string>
     */
    public function toSignedParams(string $data): array
    {
        return [
            'data' => $data,
            'type' => $this->type->value,
            'format' => $this->format->value,
            'barWidth' => $this->barWidth,
            'height' => $this->height,
            'margin' => $this->margin ?? $this->type->getDefaultMargin(),
            'foreground' => $this->foreground,
            'background' => $this->background,
            'showText' => $this->showText ? 1 : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromSignedParams(array $validated): self
    {
        $typeName = $validated['type'] ?? 'code128';
        $type = BarcodeType::tryFrom(strtolower(is_string($typeName) ? $typeName : ''));
        if ($type === null) {
            throw new InvalidArgumentException('Unknown barcode type.');
        }

        $formatName = $validated['format'] ?? 'svg';
        $format = QrFormat::tryFrom(strtolower(is_string($formatName) ? $formatName : '')) ?? QrFormat::Svg;

        return new self(
            type: $type,
            format: $format,
            barWidth: isset($validated['barWidth']) && is_numeric($validated['barWidth']) ? (int) $validated['barWidth'] : 2,
            height: isset($validated['height']) && is_numeric($validated['height']) ? (int) $validated['height'] : 60,
            margin: isset($validated['margin']) && is_numeric($validated['margin']) ? (int) $validated['margin'] : null,
            foreground: isset($validated['foreground']) && is_string($validated['foreground']) ? $validated['foreground'] : '#000000',
            background: isset($validated['background']) && is_string($validated['background']) ? $validated['background'] : '#ffffff',
            showText: ! array_key_exists('showText', $validated) || self::boolParam($validated['showText']),
        );
    }

    private static function boolParam(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var(is_scalar($value) ? (string) $value : '1', FILTER_VALIDATE_BOOLEAN);
    }
}
