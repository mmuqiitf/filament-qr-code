<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadQrAction extends Action
{
    protected string|Closure|null $qrData = null;

    protected string|Closure|null $qrFileName = null;

    protected QrFormat|string|Closure $qrFormat = QrFormat::Svg;

    protected int|Closure $qrImageSize = 400;

    protected int|Closure $qrMargin = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('download_qr');
        $this->label(__('Download QR Code'));
        $this->icon('heroicon-o-arrow-down-tray');
        $this->color('gray');

        $this->action(function (?Model $record): ?StreamedResponse {
            $data = $this->getQrData($record);
            if ($data === null || $data === '') {
                return null;
            }

            $fileName = $this->getQrFileName($record) ?? 'qrcode';

            $service = QrCodeService::make()
                ->size((int) $this->evaluate($this->qrImageSize, ['record' => $record]))
                ->margin((int) $this->evaluate($this->qrMargin, ['record' => $record]))
                ->fileName($fileName);

            $format = $this->evaluate($this->qrFormat, ['record' => $record]);
            if ($format instanceof QrFormat) {
                $service->format($format);
            } elseif (is_string($format)) {
                $service->format($format);
            }

            return $service->generate($data)->download($fileName);
        });
    }

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
