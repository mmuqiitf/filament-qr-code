<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Closure;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrDownload;
use Mmuqiitf\FilamentQrCode\Support\QrZipArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadQrBulkAction extends BulkAction
{
    use HasQrDownload;

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
        $files = [];

        foreach ($records as $record) {
            $data = $this->resolveRecordData($record);
            if ($data === null || $data === '') {
                continue;
            }

            $spec = $this->getDownloadSpec($record);

            // A logo or caption forces PNG rasterization, mirroring the
            // service, so the file extension always matches the bytes.
            $extension = ($spec->logoPath !== null && $spec->logoPath !== '') || ($spec->caption !== null && $spec->caption !== '')
                ? 'png'
                : ($spec->formatValue() ?? 'svg');

            $files[$this->resolveFileName($record).'.'.$extension] = $spec->toService()->generate($data)->getRaw();
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
