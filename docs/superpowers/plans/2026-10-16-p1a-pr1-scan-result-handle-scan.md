# P1a PR 1: ScanResult, ScanInput and handleScan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give `QrScanner` a server hook, `handleScan(code, source)`, that the browser can call through Filament v5's `callSchemaComponentMethod` and that returns a `ScanResult` array. In this PR the hook always accepts non-empty codes.

**Architecture:** A new `Concerns\InteractsWithScans` trait on `QrScanner` exposes `handleScan()` with `#[ExposedLivewireMethod]`, so `InteractsWithSchemas::callSchemaComponentMethod($componentKey, 'handleScan', [...])` can reach it. Only mounted components with that attribute are reachable, which gives the "registered scanner fields only" guard. Checks live in `runScanChecks()` (empty-code check only for now; PR 2 adds rules, lookup and closures); side effects (state write, `afterStateUpdated` hooks) live in `handleScan()`. The trait adds `componentKey` and `serverCheck` to the browser payload through the `ScanOptions::Contributors` map. No JS changes in this PR.

**Tech Stack:** PHP 8.2+, Laravel 11–13, Filament v5 (`filament/schemas`, `filament/support`), Livewire 3, Pest with snapshots, Orchestra Testbench.

**Spec:** `docs/superpowers/specs/2026-10-16-p1a-server-scan-validation-design.md` (sections 3.2, 4.1, 4.2, 4.3 steps 1, 2, 7 and 9, 5.1, 8 row 1).

## Global Constraints

- PHP files start with `declare(strict_types=1);`. Follow `.agents/skills/spatie-laravel-php`: typed properties and returns, constructor promotion, TitleCase enum cases, early returns, no docblocks that repeat type hints.
- Namespace root: `Mmuqiitf\FilamentQrCode`.
- Config keys are read from `qr-code.*`; translations from `filament-qr-code::ui.*` in `resources/lang/en/ui.php` and `resources/lang/id/ui.php` (both files get every new key).
- `ScanInput` cases are exactly `Camera = 'camera'`, `Hardware = 'hardware'`, `Upload = 'upload'`.
- `ScanResult::toClient()` keys are exactly `passes`, `code`, `source`, `message`, `record_key`.
- `handleScan` is not `#[Renderless]` (callbacks in PR 3 may `$set()` other fields).
- No behaviour change for existing fields: no JS change, `serverCheck` is `false` for every field in this PR, `QrCodeScanned`, `rejectWhen()`, `normalizeUsing()`, `scanRules()`, `onScan()`, `scanFormat()` are untouched.
- TDD: every task starts with a failing test that is run and seen to fail.
- Commands: `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `vendor/bin/pint`.

## Files

| File | Responsibility |
|---|---|
| Create `src/Enums/ScanInput.php` | Where a scan came from |
| Create `src/Support/ScanResult.php` | Immutable outcome of one scan; `toClient()` array |
| Create `src/Concerns/InteractsWithScans.php` | `handleScan()`, `runScanChecks()`, `hasServerScanChecks()`, payload contribution |
| Modify `src/Forms/Components/QrScanner.php` | `use InteractsWithScans;` |
| Modify `src/Support/ScanOptions.php` | Register `InteractsWithScans::class => 'getScanChecksScanPayload'` |
| Modify `resources/lang/en/ui.php`, `resources/lang/id/ui.php` | `scan_empty`, `scan_rejected` |
| Create `tests/Unit/ScanInputTest.php`, `tests/Unit/ScanResultTest.php` | Value-type tests |
| Create `tests/Feature/HandleScanTest.php` | Livewire round trip through `callSchemaComponentMethod` |
| Modify `tests/Feature/ScanPayloadCharacterizationTest.php` snapshot for `scanner` | Accept the added `serverCheck` key |

---

### Task 1: `ScanInput` enum

**Files:**
- Create: `src/Enums/ScanInput.php`
- Test: `tests/Unit/ScanInputTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `enum Mmuqiitf\FilamentQrCode\Enums\ScanInput: string` with `Camera`, `Hardware`, `Upload`; `ScanInput::fromClient(string $value): self` (unknown values map to `Camera`).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\ScanInput;

it('maps client source strings to scan inputs', function (string $value, ScanInput $expected): void {
    expect(ScanInput::fromClient($value))->toBe($expected);
})->with([
    'camera' => ['camera', ScanInput::Camera],
    'hardware' => ['hardware', ScanInput::Hardware],
    'upload' => ['upload', ScanInput::Upload],
    'unknown falls back to camera' => ['keyboard', ScanInput::Camera],
    'empty falls back to camera' => ['', ScanInput::Camera],
]);

it('exposes the wire values used by the browser', function (): void {
    expect(array_map(fn (ScanInput $input): string => $input->value, ScanInput::cases()))
        ->toBe(['camera', 'hardware', 'upload']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/ScanInputTest.php`
Expected: FAIL with `Class "Mmuqiitf\FilamentQrCode\Enums\ScanInput" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum ScanInput: string
{
    case Camera = 'camera';
    case Hardware = 'hardware';
    case Upload = 'upload';

    public static function fromClient(string $value): self
    {
        return self::tryFrom($value) ?? self::Camera;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/ScanInputTest.php`
Expected: PASS (6 assertions in 2 tests incl. dataset rows).

- [ ] **Step 5: Commit**

```bash
git add src/Enums/ScanInput.php tests/Unit/ScanInputTest.php
git commit -m "feat: add ScanInput enum"
```

---

### Task 2: `ScanResult` value object

**Files:**
- Create: `src/Support/ScanResult.php`
- Test: `tests/Unit/ScanResultTest.php`

**Interfaces:**
- Consumes: `ScanInput` (Task 1).
- Produces: `final readonly class Mmuqiitf\FilamentQrCode\Support\ScanResult` with public `bool $passes`, `string $code`, `?ScanInput $source`, `?Model $record`, `?string $message`; `ScanResult::accept(string $code, ?ScanInput $source, ?Model $record = null): self`; `ScanResult::reject(string $code, ?ScanInput $source, string $message): self`; `toClient(): array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed}`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;

it('builds an accepted result for the browser', function (): void {
    $record = new class extends Model
    {
        protected $guarded = [];
    };
    $record->forceFill(['id' => 42]);

    $result = ScanResult::accept('SKU-1', ScanInput::Hardware, $record);

    expect($result->passes)->toBeTrue()
        ->and($result->record)->toBe($record)
        ->and($result->toClient())->toBe([
            'passes' => true,
            'code' => 'SKU-1',
            'source' => 'hardware',
            'message' => null,
            'record_key' => 42,
        ]);
});

it('builds a rejected result with its message', function (): void {
    expect(ScanResult::reject('BAD', ScanInput::Camera, 'Nope')->toClient())->toBe([
        'passes' => false,
        'code' => 'BAD',
        'source' => 'camera',
        'message' => 'Nope',
        'record_key' => null,
    ]);
});

it('allows a null source for submit-time checks', function (): void {
    expect(ScanResult::accept('SKU-1', null)->toClient()['source'])->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/ScanResultTest.php`
Expected: FAIL with `Class "Mmuqiitf\FilamentQrCode\Support\ScanResult" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;

final readonly class ScanResult
{
    public function __construct(
        public bool $passes,
        public string $code,
        public ?ScanInput $source,
        public ?Model $record = null,
        public ?string $message = null,
    ) {}

    public static function accept(string $code, ?ScanInput $source, ?Model $record = null): self
    {
        return new self(passes: true, code: $code, source: $source, record: $record);
    }

    public static function reject(string $code, ?ScanInput $source, string $message): self
    {
        return new self(passes: false, code: $code, source: $source, message: $message);
    }

    /** @return array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed} */
    public function toClient(): array
    {
        return [
            'passes' => $this->passes,
            'code' => $this->code,
            'source' => $this->source?->value,
            'message' => $this->message,
            'record_key' => $this->record?->getKey(),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/ScanResultTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Support/ScanResult.php tests/Unit/ScanResultTest.php
git commit -m "feat: add ScanResult value object"
```

---

### Task 3: `handleScan()` through `callSchemaComponentMethod`

This task includes the call-path spike as a test: the first test proves the browser path reaches the component. If it fails for a reason other than the missing method (for example `getSchemaComponent()` returns `null` for the key), stop and report; do not switch to another call path without updating the spec.

**Files:**
- Create: `src/Concerns/InteractsWithScans.php`
- Modify: `src/Forms/Components/QrScanner.php` (add `use InteractsWithScans;` to the trait list and the import)
- Modify: `resources/lang/en/ui.php`, `resources/lang/id/ui.php`
- Test: `tests/Feature/HandleScanTest.php`

**Interfaces:**
- Consumes: `ScanInput::fromClient()` (Task 1), `ScanResult::accept()/reject()/toClient()` (Task 2), Filament `InteractsWithSchemas::callSchemaComponentMethod(string $componentKey, string $method, array $arguments = [])`, `#[Filament\Support\Components\Attributes\ExposedLivewireMethod]`.
- Produces, on `QrScanner` via `InteractsWithScans`:
  - `#[ExposedLivewireMethod] public function handleScan(string $code, string $source): array` (returns `ScanResult::toClient()`)
  - `public function runScanChecks(string $code, ?ScanInput $source): ScanResult`
  - `public function hasServerScanChecks(): bool` (returns `false` in this PR; PR 2 and PR 3 extend it)
  - Translation keys `filament-qr-code::ui.scan_empty`, `filament-qr-code::ui.scan_rejected`.

- [ ] **Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class HandleScanTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $scannerDisabled = false;

    /** @var list<string> */
    public array $normalized = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                QrScanner::make('sku')
                    ->disabled(fn (): bool => $this->scannerDisabled)
                    ->afterStateUpdated(fn (?string $state) => $this->normalized[] = (string) $state),
                TextInput::make('note'),
            ])
            ->statePath('data');
    }

    public function keyOf(string $name): string
    {
        return $this->form->getComponent(fn ($component): bool => method_exists($component, 'getName') && $component->getName() === $name)->getKey();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

it('accepts a scan through callSchemaComponentMethod and sets state', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => '  SKU-1 ', 'source' => 'hardware'])
        ->assertReturned([
            'passes' => true,
            'code' => 'SKU-1',
            'source' => 'hardware',
            'message' => null,
            'record_key' => null,
        ])
        ->assertSet('data.sku', 'SKU-1')
        ->assertSet('normalized', ['SKU-1']);
});

it('maps an unknown source to camera', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => 'A', 'source' => 'bogus'])
        ->assertReturned(fn (array $result): bool => $result['source'] === 'camera');
});

it('rejects an empty code with a translated message and leaves state alone', function (string $locale, string $message): void {
    app()->setLocale($locale);
    $test = Livewire::test(HandleScanTestComponent::class);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => '   ', 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === $message)
        ->assertSet('data.sku', null);
})->with([
    'en' => ['en', 'The scanned code is empty.'],
    'id' => ['id', 'Kode yang dipindai kosong.'],
]);

it('rejects scans on a disabled field', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class)->set('scannerDisabled', true);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => 'SKU-1', 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === __('filament-qr-code::ui.scan_rejected'))
        ->assertSet('data.sku', null);
});

it('returns null for keys that are not registered scanner fields', function (string $key): void {
    $test = Livewire::test(HandleScanTestComponent::class);
    $key = $key === 'note' ? $test->instance()->keyOf('note') : $key;

    $test->call('callSchemaComponentMethod', $key, 'handleScan', ['code' => 'SKU-1', 'source' => 'camera'])
        ->assertReturned(null)
        ->assertSet('data.sku', null);
})->with([
    'unknown key' => ['form.data.missing'],
    'non-scanner field' => ['note'],
]);

it('reports no server checks when nothing is configured', function (): void {
    expect(QrScanner::make('sku')->hasServerScanChecks())->toBeFalse();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/HandleScanTest.php`
Expected: the accept, source, empty and disabled tests FAIL because `handleScan` doesn't exist (`callSchemaComponentMethod` returns `null`, so `assertReturned([...])` fails); `hasServerScanChecks` FAILS with `Method ... hasServerScanChecks does not exist`. The "returns null" dataset passes already (that is the guard, verified here and kept as a regression test). If `keyOf()` itself throws, fix the test helper first: it must return the absolute key Filament renders for the field.

- [ ] **Step 3: Add translations**

In `resources/lang/en/ui.php`, add in alphabetical position:

```php
    'scan_empty' => 'The scanned code is empty.',
    'scan_rejected' => 'This code was rejected.',
```

In `resources/lang/id/ui.php`:

```php
    'scan_empty' => 'Kode yang dipindai kosong.',
    'scan_rejected' => 'Kode ini ditolak.',
```

- [ ] **Step 4: Write minimal implementation**

`src/Concerns/InteractsWithScans.php`:

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;

trait InteractsWithScans
{
    /** @return array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed} */
    #[ExposedLivewireMethod]
    public function handleScan(string $code, string $source): array
    {
        $input = ScanInput::fromClient($source);
        $code = trim($code);

        if ($this->isDisabled() || $this->isReadOnly()) {
            return ScanResult::reject($code, $input, __('filament-qr-code::ui.scan_rejected'))->toClient();
        }

        $result = $this->runScanChecks($code, $input);

        if (! $result->passes) {
            return $result->toClient();
        }

        $this->state($result->code);
        $this->callAfterStateUpdated();

        return $result->toClient();
    }

    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        if ($code === '') {
            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_empty'));
        }

        return ScanResult::accept($code, $source);
    }

    public function hasServerScanChecks(): bool
    {
        return false;
    }
}
```

In `src/Forms/Components/QrScanner.php` add `use Mmuqiitf\FilamentQrCode\Concerns\InteractsWithScans;` to the imports and `use InteractsWithScans;` to the trait list (alphabetical, after `HasSequentialScan`).

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/HandleScanTest.php`
Expected: PASS (all tests and dataset rows).

- [ ] **Step 6: Run the full suite and static analysis**

Run: `vendor/bin/pest && vendor/bin/phpstan analyse && vendor/bin/pint --test`
Expected: all green. `TranslationsTest` must still pass with the new keys in both locales.

- [ ] **Step 7: Commit**

```bash
git add src/Concerns/InteractsWithScans.php src/Forms/Components/QrScanner.php resources/lang/en/ui.php resources/lang/id/ui.php tests/Feature/HandleScanTest.php
git commit -m "feat: add handleScan server hook to QrScanner"
```

---

### Task 4: Browser payload (`componentKey`, `serverCheck`)

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php` (add `getScanChecksScanPayload()`)
- Modify: `src/Support/ScanOptions.php` (`Contributors` map and import)
- Modify: `tests/.pest/snapshots/Feature/ScanPayloadCharacterizationTest/it_keeps_every_scan_surface_payload_stable_with_data_set__dataset__scanner____Closure_Object____.snap` (regenerated)
- Test: `tests/Feature/HandleScanTest.php` (append)

**Interfaces:**
- Consumes: `hasServerScanChecks()` (Task 3), Filament `getKey()`.
- Produces: `public function getScanChecksScanPayload(): array{serverCheck: bool, componentKey?: string}`; `componentKey` is omitted when the component isn't mounted (no container), matching how `ScanOptions::statePathOf()` omits `statePath`.

- [ ] **Step 1: Write the failing tests** (append to `tests/Feature/HandleScanTest.php`)

```php
it('renders the component key and server check flag into the scanner payload', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class);
    $key = $test->instance()->keyOf('sku');

    $scanner = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);

    expect($scanner->getScanPayload())
        ->toHaveKey('componentKey', $key)
        ->toHaveKey('serverCheck', false);
});

it('omits the component key when the scanner is not mounted', function (): void {
    expect(QrScanner::make('sku')->getScanPayload())
        ->not->toHaveKey('componentKey')
        ->toHaveKey('serverCheck', false);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/HandleScanTest.php --filter=payload`
Expected: FAIL with `Failed asserting that an array has the key 'componentKey'` / `'serverCheck'`.

- [ ] **Step 3: Write minimal implementation**

Add to `InteractsWithScans` (and `use Throwable;` to its imports):

```php
    /** @return array{serverCheck: bool, componentKey?: string} */
    public function getScanChecksScanPayload(): array
    {
        $payload = ['serverCheck' => $this->hasServerScanChecks()];

        try {
            $key = $this->getKey();
        } catch (Throwable) {
            return $payload;
        }

        if (blank($key)) {
            return $payload;
        }

        return [...$payload, 'componentKey' => $key];
    }
```

In `src/Support/ScanOptions.php` add `use Mmuqiitf\FilamentQrCode\Concerns\InteractsWithScans;` and a map entry:

```php
    private const Contributors = [
        HasFeedback::class => 'getFeedbackScanPayload',
        HasHardwareScanner::class => 'getHardwareScannerScanPayload',
        HasCameraScanning::class => 'getCameraScanPayload',
        HasSequentialScan::class => 'getSequentialScanPayload',
        InteractsWithScans::class => 'getScanChecksScanPayload',
    ];
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/HandleScanTest.php`
Expected: PASS.

- [ ] **Step 5: Update the characterization snapshot**

Run: `vendor/bin/pest tests/Feature/ScanPayloadCharacterizationTest.php`
Expected: only the `scanner` row FAILS, and the diff is exactly one added line `"serverCheck": false`. If any other key or row changes, stop and investigate.
Then run: `vendor/bin/pest tests/Feature/ScanPayloadCharacterizationTest.php --update-snapshots` and re-run without the flag. Expected: PASS.

- [ ] **Step 6: Run the full suite**

Run: `vendor/bin/pest && vendor/bin/phpstan analyse && vendor/bin/pint --test && npm test`
Expected: all green (`npm test` confirms no JS regression; there are no JS changes).

- [ ] **Step 7: Commit**

```bash
git add src/Concerns/InteractsWithScans.php src/Support/ScanOptions.php tests/Feature/HandleScanTest.php tests/.pest/snapshots
git commit -m "feat: expose scanner component key and server check flag to the browser"
```

---

## Out of scope for PR 1 (later PRs per spec section 8)

- PR 2: `validateUsing()`, `lookup()`, full `runScanChecks()` order, exception handling (`scan_failed`, `record_not_found`), `ScanChecksRule` for submit.
- PR 3: `onScanned()`/`onRejected()`, `clearOnReject()`, `ScanAccepted`/`ScanRejected`/`QrCodeScanned` events.
- PR 4: JS pipeline, `cooldown()`, payload keys `clearOnReject`/`cooldownMs`/`scanFailedMessage`, hardware stopgap.
- PR 5: outcome feedback.

## Owner rulings (2026-10-16)

1. Submit-time rejections show the normal field error only; `onRejected`/`ScanRejected` are live-scan only (affects PR 2/3, not this PR).
2. A disabled or read-only field rejects with `scan_rejected` (Task 3 test "rejects scans on a disabled field").
3. Verify the absolute component key format at runtime first (Task 3 Step 2 note) before writing the implementation.
