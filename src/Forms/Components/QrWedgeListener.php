<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;

class QrWedgeListener extends Component
{
    use HasFeedback;
    use HasHardwareScanner;

    protected string $view = 'filament-qr-code::components.qr-wedge-listener';

    /**
     * @var array<int, string>|Closure
     */
    protected array|Closure $fields = [];

    protected bool|Closure $autoFocusNext = true;

    /**
     * @param  array<int, string>|Closure  $fields
     */
    public static function make(array|Closure $fields = []): static
    {
        $static = app(static::class);
        $static->fields($fields);

        return $static;
    }

    /**
     * @param  array<int, string>|Closure  $fields
     */
    public function fields(array|Closure $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function autoFocusNext(bool|Closure $condition = true): static
    {
        $this->autoFocusNext = $condition;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getFields(): array
    {
        $fields = $this->evaluate($this->fields);
        if (! is_array($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                $result[] = $field;
            }
        }

        return $result;
    }

    public function isAutoFocusNext(): bool
    {
        return (bool) $this->evaluate($this->autoFocusNext);
    }
}
