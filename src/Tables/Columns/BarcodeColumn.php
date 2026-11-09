<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Support\Facades\URL;
use Mmuqiitf\FilamentQrCode\Concerns\HasBarcodeOptions;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Throwable;

/**
 * Table column that shows a compact 1D barcode of its state or data()
 * through the signed filamentQrCode.barcode route. Defaults are bar
 * width 1, 32px high and no text line.
 */
class BarcodeColumn extends Column
{
    use HasBarcodeOptions {
        getBarcodeRender as protected renderInlineBarcode;
    }

    protected string $view = 'filament-qr-code::components.barcode-column';

    /**
     * View data whose image is a signed URL. Falls back to a data URI when
     * the route cannot be signed. Forms and infolists stay inline.
     *
     * @internal Used by the package views.
     *
     * @return array{uri: string, alt: string, extension: string, error: ?string}
     */
    public function getBarcodeRender(): array
    {
        $data = $this->getBarcodeData();
        if ($data === null || $data === '') {
            return $this->emptyBarcodeRender();
        }

        $spec = $this->getBarcodeRenderSpec();

        try {
            $text = $spec->encodedText($data);
        } catch (InvalidBarcodeException $e) {
            return $this->invalidBarcodeRender($e);
        }

        try {
            $params = $spec->toSignedParams($data);
            $ttl = config('qr-code.image_route.ttl');
            $uri = is_numeric($ttl) && (int) $ttl > 0
                ? URL::temporarySignedRoute('filamentQrCode.barcode', now()->addMinutes((int) $ttl), $params)
                : URL::signedRoute('filamentQrCode.barcode', $params);
        } catch (Throwable) {
            return $this->renderInlineBarcode();
        }

        return [
            'uri' => $uri,
            'alt' => $spec->type->getLabel().' barcode: '.$text,
            'extension' => $spec->format->getExtension(),
            'error' => null,
        ];
    }

    protected function getDefaultBarWidth(): int
    {
        return 1;
    }

    protected function getDefaultBarHeight(): int
    {
        return 32;
    }

    protected function getDefaultShowText(): bool
    {
        return false;
    }

    protected function getDefaultDownloadable(): bool
    {
        return false;
    }
}
