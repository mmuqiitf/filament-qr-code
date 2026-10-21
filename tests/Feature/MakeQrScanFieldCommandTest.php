<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::deleteDirectory(app_path('Filament/Forms/Components'));
    File::deleteDirectory(base_path('stubs/filament-qr-code'));
});

afterEach(function (): void {
    File::deleteDirectory(app_path('Filament/Forms/Components'));
    File::deleteDirectory(base_path('stubs/filament-qr-code'));
});

it('generates a QrScanner subclass with a setUp() stub', function (): void {
    $this->artisan('make:qr-scan-field', ['name' => 'SkuScanner'])->assertSuccessful();

    $path = app_path('Filament/Forms/Components/SkuScanner.php');
    $snapshot = __DIR__.'/../Fixtures/generated/SkuScanner.php.txt';

    if (! file_exists($snapshot)) {
        file_put_contents($snapshot, (string) file_get_contents($path));
    }

    expect(file_get_contents($path))->toBe(file_get_contents($snapshot));
});

it('generates a QrScanSequence subclass with --sequence', function (): void {
    $this->artisan('make:qr-scan-field', ['name' => 'ReceivingSequence', '--sequence' => true])->assertSuccessful();

    $contents = (string) file_get_contents(app_path('Filament/Forms/Components/ReceivingSequence.php'));

    expect($contents)
        ->toContain('namespace App\Filament\Forms\Components;')
        ->toContain('class ReceivingSequence extends QrScanSequence')
        ->toContain('use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;')
        ->toContain('protected function setUp(): void')
        ->toContain('ScanStep::make(');

    expect(fn () => eval(str_replace('<?php', '', $contents)))->not->toThrow(Throwable::class);
});

it('refuses to overwrite an existing class without --force', function (): void {
    $path = app_path('Filament/Forms/Components/SkuScanner.php');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<?php // mine');

    $this->artisan('make:qr-scan-field', ['name' => 'SkuScanner']);

    expect(file_get_contents($path))->toBe('<?php // mine');

    $this->artisan('make:qr-scan-field', ['name' => 'SkuScanner', '--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->toContain('class SkuScanner extends QrScanner');
});

it('uses a published stub when one exists', function (): void {
    File::ensureDirectoryExists(base_path('stubs/filament-qr-code'));
    File::put(base_path('stubs/filament-qr-code/qr-scan-field.stub'), "<?php\n\nnamespace {{ namespace }};\n\n// custom stub\nclass {{ class }} {}\n");

    $this->artisan('make:qr-scan-field', ['name' => 'SkuScanner'])->assertSuccessful();

    expect(file_get_contents(app_path('Filament/Forms/Components/SkuScanner.php')))->toContain('// custom stub');
});

it('publishes the stubs under the filament-qr-code-stubs tag', function (): void {
    $this->artisan('vendor:publish', ['--tag' => 'filament-qr-code-stubs'])->assertSuccessful();

    expect(base_path('stubs/filament-qr-code/qr-scan-field.stub'))->toBeFile()
        ->and(base_path('stubs/filament-qr-code/qr-scan-sequence-field.stub'))->toBeFile();
});
