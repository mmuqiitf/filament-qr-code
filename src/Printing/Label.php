<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Printing;

use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Gs1\DigitalLink;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

/**
 * One cell on a label sheet: an optional QR, an optional barcode, and text.
 */
final class Label
{
    protected ?string $qrPayload = null;

    protected ?string $barcodePayload = null;

    protected BarcodeType $barcodeType = BarcodeType::Code128;

    protected ?string $captionText = null;

    protected ?string $headingText = null;

    protected int $qrSize = 80;

    /** Start a blank label. */
    public static function make(): self
    {
        return new self;
    }

    /** Encode this string as a QR code. */
    public function qr(?string $data): self
    {
        $this->qrPayload = self::nullableString($data);

        return $this;
    }

    /**
     * Encode an uncompressed GS1 Digital Link URI as the QR payload.
     *
     * @param  ElementString|array<int|string, mixed>  $elements
     */
    public function digitalLink(ElementString|array $elements, ?string $domain = null): self
    {
        $this->qrPayload = DigitalLink::build($elements, $domain);

        return $this;
    }

    /** Draw a barcode of this symbology, including 2D types. */
    public function barcode(?string $data, BarcodeType|string $type = BarcodeType::Code128): self
    {
        $this->barcodePayload = self::nullableString($data);
        $this->barcodeType = $type instanceof BarcodeType ? $type : BarcodeType::from($type);

        return $this;
    }

    /** Text under the symbols. */
    public function caption(?string $text): self
    {
        $this->captionText = self::nullableString($text);

        return $this;
    }

    /** Text above the symbols. */
    public function heading(?string $text): self
    {
        $this->headingText = self::nullableString($text);

        return $this;
    }

    /** QR module size in pixels (default 80). */
    public function qrSize(int $pixels): self
    {
        $this->qrSize = $pixels;

        return $this;
    }

    /** The string encoded in the QR, including a Digital Link URI. */
    public function qrPayload(): ?string
    {
        return $this->qrPayload;
    }

    public function getCaption(): ?string
    {
        return $this->captionText;
    }

    public function getHeading(): ?string
    {
        return $this->headingText;
    }

    public function isEmpty(): bool
    {
        return $this->qrPayload === null && $this->barcodePayload === null;
    }

    /** Data URI for the QR `<img>`, or null when there is no QR. */
    public function qrDataUri(?QrFormat $format = null): ?string
    {
        if ($this->qrPayload === null) {
            return null;
        }

        return (new QrCodeService)
            ->size($this->qrSize)
            ->margin(0)
            ->format($format ?? QrFormat::Svg)
            ->generate($this->qrPayload)
            ->toDataUri();
    }

    /** Data URI for the barcode `<img>`, or null when there is no barcode. */
    public function barcodeDataUri(?QrFormat $format = null): ?string
    {
        if ($this->barcodePayload === null) {
            return null;
        }

        $service = (new BarcodeService)
            ->type($this->barcodeType)
            ->format($format ?? QrFormat::Svg);

        if ($this->barcodeType->isTwoDimensional()) {
            $service->barWidth(2);
        } else {
            $service->height(24)->barWidth(1);
        }

        return $service->generate($this->barcodePayload)->toDataUri();
    }

    protected static function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
