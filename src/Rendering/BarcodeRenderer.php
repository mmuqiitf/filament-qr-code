<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

/**
 * Draws an encoded BarcodeSymbol as SVG or PNG. Shares the QR rendering
 * layer: QrFormat, HexColor, QrRenderCache (via BarcodeService) and the
 * caption compositor for the PNG text line. Stateless.
 *
 * @internal Not an extension point; may change without notice. Use BarcodeService.
 */
final class BarcodeRenderer
{
    /** Gap in pixels between the bars and the human-readable text. */
    private const TEXT_GAP = 4;

    public function __construct(
        private readonly ?bool $imagick = null,
        private readonly QrCaptionCompositor $caption = new QrCaptionCompositor,
    ) {}

    public function render(BarcodeRenderOptions $options): string
    {
        $symbol = BarcodeSymbol::encode($options->type, $options->data);

        return $options->format === QrFormat::Svg
            ? $this->svg($symbol, $options)
            : $this->png($symbol, $options);
    }

    public static function accessibleName(BarcodeRenderOptions $options, string $text): string
    {
        return "{$options->type->getLabel()} barcode: {$text}";
    }

    private function svg(BarcodeSymbol $symbol, BarcodeRenderOptions $options): string
    {
        $fg = $this->hex($options->foregroundColor);
        $bg = $this->hex($options->backgroundColor);
        $width = $this->width($symbol, $options);
        $height = $options->height + ($options->showText ? $options->fontSize + self::TEXT_GAP * 2 : 0);
        $label = $this->escape(self::accessibleName($options, $symbol->text));

        $rects = '';
        foreach ($this->pixelBars($symbol, $options) as [$x, $y, $w, $h]) {
            $rects .= sprintf('<rect x="%s" y="%s" width="%s" height="%s"/>', $this->number($x), $this->number($y), $this->number($w), $this->number($h));
        }

        $text = '';
        if ($options->showText) {
            $text = sprintf(
                '<text x="%s" y="%d" text-anchor="middle" font-family="monospace" font-size="%d" fill="%s">%s</text>',
                $this->number($width / 2),
                $options->height + self::TEXT_GAP + $options->fontSize,
                $options->fontSize,
                $fg,
                $this->escape($symbol->text),
            );
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            ."<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\" viewBox=\"0 0 {$width} {$height}\" role=\"img\" aria-label=\"{$label}\">"
            ."<title>{$label}</title>"
            ."<rect width=\"{$width}\" height=\"{$height}\" fill=\"{$bg}\"/>"
            ."<g fill=\"{$fg}\" shape-rendering=\"crispEdges\">{$rects}</g>"
            .$text
            ."</svg>\n";
    }

    private function png(BarcodeSymbol $symbol, BarcodeRenderOptions $options): string
    {
        $png = ($this->imagick ?? extension_loaded('imagick'))
            ? $this->pngWithImagick($symbol, $options)
            : $this->pngWithGd($symbol, $options);

        if (! $options->showText) {
            return $png;
        }

        return $this->caption->applyText(
            $png,
            $symbol->text,
            $options->fontSize,
            $this->hex($options->foregroundColor),
            $options->fontPath,
            $this->hex($options->backgroundColor),
        );
    }

    private function pngWithGd(BarcodeSymbol $symbol, BarcodeRenderOptions $options): string
    {
        $image = imagecreatetruecolor(max(1, $this->width($symbol, $options)), max(1, $options->height));
        $bg = HexColor::toRgb($options->backgroundColor);
        $fg = HexColor::toRgb($options->foregroundColor);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, $bg['r'], $bg['g'], $bg['b']));
        $ink = (int) imagecolorallocate($image, $fg['r'], $fg['g'], $fg['b']);

        foreach ($this->pixelBars($symbol, $options) as [$x, $y, $w, $h]) {
            [$x0, $y0, $x1, $y1] = $this->pixelBox($x, $y, $w, $h);
            imagefilledrectangle($image, $x0, $y0, $x1, $y1, $ink);
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function pngWithImagick(BarcodeSymbol $symbol, BarcodeRenderOptions $options): string
    {
        $draw = new ImagickDraw;
        $draw->setStrokeAntialias(false);
        $draw->setFillColor(new ImagickPixel($this->hex($options->foregroundColor)));

        foreach ($this->pixelBars($symbol, $options) as [$x, $y, $w, $h]) {
            [$x0, $y0, $x1, $y1] = $this->pixelBox($x, $y, $w, $h);
            $draw->rectangle($x0, $y0, $x1, $y1);
        }

        $image = new Imagick;
        $image->newImage(max(1, $this->width($symbol, $options)), max(1, $options->height), new ImagickPixel($this->hex($options->backgroundColor)));
        $image->setImageFormat('png');
        $image->drawImage($draw);
        $png = $image->getImageBlob();
        $image->destroy();

        return $png;
    }

    private function width(BarcodeSymbol $symbol, BarcodeRenderOptions $options): int
    {
        return ($symbol->columns + 2 * $options->margin) * $options->barWidth;
    }

    /**
     * Bars in pixels: modules scaled by the bar width horizontally and
     * stretched to the requested height vertically.
     *
     * @return list<array{float, float, float, float}>
     */
    private function pixelBars(BarcodeSymbol $symbol, BarcodeRenderOptions $options): array
    {
        $rowHeight = $options->height / max(1, $symbol->rows);

        return array_map(fn (array $bar): array => [
            (float) (($bar[0] + $options->margin) * $options->barWidth),
            $bar[1] * $rowHeight,
            (float) ($bar[2] * $options->barWidth),
            $bar[3] * $rowHeight,
        ], $symbol->bars);
    }

    /** @return array{int, int, int, int} inclusive pixel corners */
    private function pixelBox(float $x, float $y, float $w, float $h): array
    {
        return [(int) round($x), (int) round($y), (int) round($x + $w) - 1, (int) round($y + $h) - 1];
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /** Normalised #rrggbb, validated by HexColor. */
    private function hex(string $color): string
    {
        $rgb = HexColor::toRgb($color);

        return sprintf('#%02x%02x%02x', $rgb['r'], $rgb['g'], $rgb['b']);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
