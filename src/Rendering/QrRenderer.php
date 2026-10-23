<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererInterface;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

/**
 * Encodes a payload into SVG or PNG bytes, then applies logo and caption.
 * Stateless: no caching here (see QrRenderCache).
 */
final class QrRenderer
{
    public function __construct(
        private readonly QrLogoCompositor $logo = new QrLogoCompositor,
        private readonly QrCaptionCompositor $caption = new QrCaptionCompositor,
    ) {}

    public function render(QrRenderOptions $options): string
    {
        $fill = $this->fill($options);

        if ($options->format === QrFormat::Svg) {
            return $this->write(new ImageRenderer($this->style($options, $fill), new SvgImageBackEnd), $options);
        }

        $png = extension_loaded('imagick')
            ? $this->write(new ImageRenderer($this->style($options, $fill), new ImagickImageBackEnd), $options)
            : $this->write(new GDLibRenderer($options->size, $options->margin, imageFormat: 'png', fill: $fill), $options);

        if ($options->hasLogoFile()) {
            $png = $this->logo->apply($png, (string) $options->logoPath, $options->logoSize);
        }

        if ($options->hasCaption()) {
            $png = $this->caption->apply($png, $options);
        }

        return $png;
    }

    private function write(RendererInterface $renderer, QrRenderOptions $options): string
    {
        return (new Writer($renderer))->writeString($options->data, 'UTF-8', $options->errorCorrectionLevel);
    }

    private function fill(QrRenderOptions $options): Fill
    {
        $fg = HexColor::toRgb($options->foregroundColor);
        $bg = HexColor::toRgb($options->backgroundColor);

        return Fill::uniformColor(
            new Rgb($bg['r'], $bg['g'], $bg['b']),
            new Rgb($fg['r'], $fg['g'], $fg['b']),
        );
    }

    private function style(QrRenderOptions $options, Fill $fill): RendererStyle
    {
        return new RendererStyle($options->size, $options->margin, null, null, $fill);
    }
}
