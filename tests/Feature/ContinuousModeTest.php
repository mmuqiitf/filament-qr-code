<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Closure;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\ContinuousDisplay;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class ContinuousModeTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<string> */
    public array $scanned = [];

    /** @var list<string|null> */
    public array $updates = [];

    public static ?Closure $configure = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $scanner = QrScanner::make('sku')
            ->mode(ScanMode::Continuous)
            ->afterStateUpdated(function (?string $state): void {
                $this->updates[] = $state;
            })
            ->onScanned(function (string $code): void {
                $this->scanned[] = $code;
            });

        if (self::$configure instanceof Closure) {
            (self::$configure)($scanner);
        }

        return $form->schema([$scanner])->statePath('data');
    }

    public function keyOfScanner(): string
    {
        return $this->form->getComponent(fn ($component): bool => $component instanceof QrScanner)->getKey();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

function scanContinuously(mixed $test, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', $test->instance()->keyOfScanner(), 'handleScan', ['code' => $code, 'source' => 'hardware']);
}

beforeEach(function (): void {
    ContinuousModeTestComponent::$configure = null;
});

it('continuous mode keeps state empty and calls onScanned per accepted code', function (): void {
    $test = Livewire::test(ContinuousModeTestComponent::class);

    foreach (['A-1', 'A-2', 'A-3'] as $code) {
        scanContinuously($test, $code)
            ->assertReturned(['passes' => true, 'code' => $code, 'source' => 'hardware', 'message' => null, 'record_key' => null])
            ->assertSet('data.sku', null);
    }

    $test->assertSet('scanned', ['A-1', 'A-2', 'A-3']);
});

it('keeps the last code with the last_code display', function (): void {
    ContinuousModeTestComponent::$configure = fn (QrScanner $scanner) => $scanner->continuousDisplay(ContinuousDisplay::LastCode);
    $test = Livewire::test(ContinuousModeTestComponent::class);

    scanContinuously($test, 'A-1');
    scanContinuously($test, 'A-2')->assertSet('data.sku', 'A-2');
});

it('runs afterStateUpdated once per scan', function (): void {
    $test = Livewire::test(ContinuousModeTestComponent::class);

    scanContinuously($test, 'A-1');
    scanContinuously($test, 'A-2');

    $test->assertSet('updates', ['A-1', 'A-2']);
});

it('fires the scan events once per accepted code', function (): void {
    Event::fake([ScanAccepted::class, QrCodeScanned::class]);
    $test = Livewire::test(ContinuousModeTestComponent::class);

    scanContinuously($test, 'A-1');
    scanContinuously($test, 'A-1');

    Event::assertDispatchedTimes(ScanAccepted::class, 2);
    Event::assertDispatchedTimes(QrCodeScanned::class, 2);
});

it('goes through handleScan even without checks', function (): void {
    expect(QrScanner::make('sku')->mode(ScanMode::Continuous)->hasServerScanChecks())->toBeTrue()
        ->and(QrScanner::make('sku')->hasServerScanChecks())->toBeFalse();
});

it('uses the continuous cooldown default', function (): void {
    expect(QrScanner::make('sku')->mode(ScanMode::Continuous)->getCooldownMs())->toBe(300)
        ->and(QrScanner::make('sku')->mode(ScanMode::Continuous)->cooldown(50)->getCooldownMs())->toBe(50)
        ->and(QrScanner::make('sku')->validateUsing(['min:2'])->getCooldownMs())->toBe(1500);

    config()->set('qr-code.scan.continuous.cooldown_ms', 120);

    expect(QrScanner::make('sku')->mode(ScanMode::Continuous)->getCooldownMs())->toBe(120);
});

it('reads continuous options from config with per-field overrides', function (): void {
    $field = QrScanner::make('sku')->mode(ScanMode::Continuous);

    expect($field->getMode())->toBe(ScanMode::Continuous)
        ->and($field->isContinuous())->toBeTrue()
        ->and($field->getContinuousDisplay())->toBe(ContinuousDisplay::Empty)
        ->and($field->shouldPauseOnReject())->toBeFalse()
        ->and($field->shouldKeepCameraOpen())->toBeFalse();

    config()->set('qr-code.scan.continuous', [
        'display' => 'last_code',
        'pause_on_reject' => true,
        'keep_camera_open' => true,
        'cooldown_ms' => 300,
    ]);

    expect($field->getContinuousDisplay())->toBe(ContinuousDisplay::LastCode)
        ->and($field->shouldPauseOnReject())->toBeTrue()
        ->and($field->shouldKeepCameraOpen())->toBeTrue()
        ->and($field->pauseOnReject(false)->shouldPauseOnReject())->toBeFalse()
        ->and($field->keepCameraOpen(false)->shouldKeepCameraOpen())->toBeFalse()
        ->and($field->continuousDisplay(ContinuousDisplay::Empty)->getContinuousDisplay())->toBe(ContinuousDisplay::Empty);
});

it('falls back to empty for an invalid display config', function (): void {
    config()->set('qr-code.scan.continuous.display', 'sideways');

    expect(QrScanner::make('sku')->getContinuousDisplay())->toBe(ContinuousDisplay::Empty);
});

it('ships the continuous config defaults', function (): void {
    expect(config('qr-code.scan.continuous'))->toBe([
        'display' => 'empty',
        'pause_on_reject' => false,
        'keep_camera_open' => false,
        'cooldown_ms' => 300,
    ]);
});

it('exposes the continuous options in the payload', function (): void {
    $payload = QrScanner::make('sku')->mode(ScanMode::Continuous)->pauseOnReject()->keepCameraOpen()->getScanPayload();

    expect($payload['mode'])->toBe('continuous')
        ->and($payload['continuousDisplay'])->toBe('empty')
        ->and($payload['pauseOnReject'])->toBeTrue()
        ->and($payload['keepCameraOpen'])->toBeTrue()
        ->and($payload['serverCheck'])->toBeTrue()
        ->and($payload['cooldownMs'])->toBe(300);
});

it('keeps single mode payload values unchanged', function (): void {
    $payload = QrScanner::make('sku')->getScanPayload();

    expect($payload['mode'])->toBe('single')
        ->and($payload['serverCheck'])->toBeFalse()
        ->and($payload['cooldownMs'])->toBe(0);
});

it('logs one warning for required() on a continuous field in local env', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    Log::spy();
    $field = QrScanner::make('sku')->mode(ScanMode::Continuous)->required();

    $field->getScanPayload();
    $field->getScanPayload();

    Log::shouldHaveReceived('warning')->once();
});

it('does not warn outside the local env', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    Log::spy();

    QrScanner::make('sku')->mode(ScanMode::Continuous)->required()->getScanPayload();

    Log::shouldNotHaveReceived('warning');
});
