<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;

class QrScanSequence extends Component
{
    use HasFeedback;
    use HasHardwareScanner;

    protected string $view = 'filament-qr-code::components.qr-scan-sequence';

    /**
     * @var array<int, array{key: string, label: string}|string>|Closure
     */
    protected array|Closure $scanFields = [];

    protected int|Closure $fps = 15;

    protected int|Closure $qrbox = 250;

    /**
     * @param array<int, array{key: string, label: string}|string>|Closure $fields
     */
    public static function make(array|Closure $fields = []): static
    {
        $static = app(static::class);
        $static->fields($fields);

        return $static;
    }

    /**
     * @param array<int, array{key: string, label: string}|string>|Closure $fields
     */
    public function fields(array|Closure $fields): static
    {
        $this->scanFields = $fields;

        return $this;
    }

    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;

        return $this;
    }

    public function qrbox(int|Closure $qrbox): static
    {
        $this->qrbox = $qrbox;

        return $this;
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function getScanFields(): array
    {
        $raw = $this->evaluate($this->scanFields);
        if (! is_array($raw)) {
            return [];
        }

        $formatted = [];
        foreach ($raw as $key => $item) {
            if (is_string($item)) {
                $formatted[] = [
                    'key' => is_string($key) ? $key : $item,
                    'label' => ucwords(str_replace(['_', '-'], ' ', is_string($key) ? $item : $item)),
                ];
            } elseif (is_array($item)) {
                $itemKey = isset($item['key']) && is_string($item['key']) ? $item['key'] : (string) $key;
                $itemLabel = isset($item['label']) && is_string($item['label']) ? $item['label'] : ucwords(str_replace(['_', '-'], ' ', $itemKey));

                $formatted[] = [
                    'key' => $itemKey,
                    'label' => $itemLabel,
                ];
            }
        }

        return $formatted;
    }

    public function getFps(): int
    {
        return (int) $this->evaluate($this->fps);
    }

    public function getQrbox(): int
    {
        return (int) $this->evaluate($this->qrbox);
    }
}
