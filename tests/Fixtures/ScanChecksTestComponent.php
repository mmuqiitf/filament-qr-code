<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class ScanChecksTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public static ?Closure $configureScanner = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $submitted = false;

    public static function test(Closure $configure): Testable
    {
        self::$configureScanner = $configure;

        return Livewire::test(self::class);
    }

    public static function scan(Testable $test, string $code, string $source = 'camera'): Testable
    {
        return $test->call('callSchemaComponentMethod', 'form.sku', 'handleScan', ['code' => $code, 'source' => $source]);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $scanner = QrScanner::make('sku');

        if (self::$configureScanner instanceof Closure) {
            $scanner = (self::$configureScanner)($scanner);
        }

        return $form
            ->schema([
                $scanner,
                TextInput::make('note'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
        $this->submitted = true;
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
