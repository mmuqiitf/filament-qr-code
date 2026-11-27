<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\Concerns\InteractsWithTestForm;

class ScanChecksTestComponent extends Component implements HasForms
{
    use InteractsWithTestForm;

    public static ?Closure $configureScanner = null;

    public static function test(Closure $configure): Testable
    {
        self::$configureScanner = $configure;

        return Livewire::test(self::class);
    }

    public static function scan(Testable $test, string $code, string $source = 'camera'): Testable
    {
        return $test->call('callSchemaComponentMethod', 'form.sku', 'handleScan', ['code' => $code, 'source' => $source]);
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
}
