<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrDownload;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadQrAction extends Action
{
    use HasQrDownload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('download_qr');
        $this->label(__('filament-qr-code::ui.download_qr_code'));
        $this->icon('heroicon-o-arrow-down-tray');
        $this->color('gray');

        $this->action(function (?Model $record): ?StreamedResponse {
            $data = $this->getQrData($record);
            if ($data === null || $data === '') {
                return null;
            }

            $fileName = $this->getQrFileName($record) ?? 'qrcode';

            $service = $this->getDownloadSpec($record)->toService()->fileName($fileName);

            return $service->generate($data)->download($fileName);
        });
    }

    public function getQrData(?Model $record): ?string
    {
        if ($this->qrData !== null) {
            $data = $this->evaluate($this->qrData, ['record' => $record]);

            return is_scalar($data) ? (string) $data : null;
        }

        if ($record !== null && isset($record->qr_code)) {
            return (string) $record->qr_code;
        }

        if ($record !== null && isset($record->code)) {
            return (string) $record->code;
        }

        return null;
    }

    public function getQrFileName(?Model $record): ?string
    {
        if ($this->qrFileName !== null) {
            $name = $this->evaluate($this->qrFileName, ['record' => $record]);

            return is_scalar($name) ? (string) $name : null;
        }

        if ($record !== null && isset($record->name)) {
            return (string) $record->name;
        }

        return 'qrcode';
    }
}
