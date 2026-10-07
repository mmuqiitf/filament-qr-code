<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Closure;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrRendering;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Support\QrZipArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadQrBulkAction extends BulkAction
{
    use HasQrRendering;

    protected string|Closure|null $qrData = null;

    protected string|Closure|null $qrFileName = null;

    protected QrFormat|string|Closure $qrFormat = QrFormat::Svg;

    protected int|Closure $qrImageSize = 400;

    protected int|Closure $qrMargin = 2;

    protected string|Closure $zipName = 'qr-codes.zip';

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('download_qr_bulk');
        $this->label(__('filament-qr-code::ui.download_qr_bulk'));
        $this->icon('heroicon-o-archive-box-arrow-down');
        $this->color('gray');

        $this->action(function (Collection $records): ?StreamedResponse {
            $files = $this->recordsToFiles($records);

            if ($files === []) {
                Notification::make()->title(__('filament-qr-code::ui.no_data'))->warning()->send();

                return null;
            }

            $archive = QrZipArchive::fromFiles($files);
            $zipName = (string) $this->evaluate($this->zipName);

            return response()->streamDownload(
                static function () use ($archive): void {
                    echo $archive;
                },
                $zipName !== '' ? $zipName : 'qr-codes.zip',
                ['Content-Type' => 'application/zip'],
            );
        });
    }

    /**
     * Attribute name (or closure receiving the record) resolving each row's
     * encoded value.
     */
    public function qrData(string|Closure|null $data): static
    {
        $this->qrData = $data;

        return $this;
    }

    public function qrFileName(string|Closure|null $name): static
    {
        $this->qrFileName = $name;

        return $this;
    }

    public function qrFormat(QrFormat|string|Closure $format): static
    {
        $this->qrFormat = $format;

        return $this;
    }

    public function qrImageSize(int|Closure $size): static
    {
        $this->qrImageSize = $size;

        return $this;
    }

    public function qrMargin(int|Closure $margin): static
    {
        $this->qrMargin = $margin;

        return $this;
    }

    public function zipName(string|Closure $name): static
    {
        $this->zipName = $name;

        return $this;
    }

    /**
     * @param  Collection<int, mixed>  $records
     * @return array<string, string> filename => raw image bytes
     */
    public function recordsToFiles(Collection $records): array
    {
        $format = $this->evaluate($this->qrFormat);
        $extension = $format instanceof QrFormat
            ? $format->getExtension()
            : (strtolower((string) $format) === 'png' ? 'png' : 'svg');

        $files = [];

        foreach ($records as $record) {
            $data = $this->resolveRecordData($record);
            if ($data === null || $data === '') {
                continue;
            }

            $service = $this->buildQrCodeService(
                size: (int) $this->evaluate($this->qrImageSize, ['record' => $record]),
                margin: (int) $this->evaluate($this->qrMargin, ['record' => $record]),
                foreground: '#000000',
                background: '#ffffff',
                format: $format instanceof QrFormat || is_string($format) ? $format : null,
            );

            $files[$this->resolveFileName($record).'.'.$extension] = $service->generate($data)->getRaw();
        }

        return $files;
    }

    private function resolveRecordData(mixed $record): ?string
    {
        if ($this->qrData instanceof Closure) {
            $data = $this->evaluate($this->qrData, ['record' => $record]);

            return is_scalar($data) ? (string) $data : null;
        }

        if (is_string($this->qrData) && $this->qrData !== '') {
            $value = $record instanceof Model
                ? $record->getAttribute($this->qrData)
                : data_get($record, $this->qrData);

            return is_scalar($value) ? (string) $value : null;
        }

        if ($record instanceof Model) {
            foreach (['qr_code', 'code', 'sku', 'barcode'] as $key) {
                $value = $record->getAttribute($key);

                if (is_scalar($value) && (string) $value !== '') {
                    return (string) $value;
                }
            }
        }

        return null;
    }

    private function resolveFileName(mixed $record): string
    {
        if ($this->qrFileName instanceof Closure) {
            $name = $this->evaluate($this->qrFileName, ['record' => $record]);

            return is_scalar($name) && (string) $name !== '' ? (string) $name : 'qrcode';
        }

        if (is_string($this->qrFileName) && $this->qrFileName !== '') {
            return $this->qrFileName;
        }

        if ($record instanceof Model) {
            $key = $record->getKey();

            return is_scalar($key) ? 'qr-'.$key : 'qrcode';
        }

        return 'qrcode';
    }
}
