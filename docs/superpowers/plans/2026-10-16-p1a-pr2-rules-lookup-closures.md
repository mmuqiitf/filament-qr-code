# P1a PR 2: Rules, Lookup and Closure Checks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `QrScanner::handleScan()` run real server checks on each live scan (Laravel rules, a model lookup and closure checks, in that order, stopping at the first failure), and re-run the same checks when the form is submitted.

**Architecture:** All checks live in `InteractsWithScans::runScanChecks()`, which PR 1 created with an empty-code check only. PR 2 adds the configuration API (`validateUsing()`, `lookup()`), the three pipeline stages, exception handling, and the cooldown default rule. A new `Validation\ScanChecksRule` calls `runScanChecks()` at submit time, so live and submit checks can't drift apart. `handleScan()` itself is unchanged apart from passing the lookup record through.

**Tech Stack:** PHP 8.2+, Laravel 11–13 (`Validator`, Eloquent), Filament v5 (`Field::rule()`, `evaluate()`), Livewire 3, Pest, Orchestra Testbench with the `testing` sqlite connection.

**Spec:** `docs/superpowers/specs/2026-10-16-p1a-server-scan-validation-design.md`, sections 4.2, 4.3 (steps 3–6), 4.4, 4.5, 5.4, 6, 9 and row 2 of section 8.

## Global Constraints

- PHP files start with `declare(strict_types=1);`. Follow `.agents/skills/spatie-laravel-php`: typed properties and returns, early returns, no docblocks that repeat type hints.
- Translations use `filament-qr-code::ui.*` and go in both `resources/lang/en/ui.php` and `resources/lang/id/ui.php`. Config is read with `Support\ConfigValue` from `qr-code.*`.
- Pipeline order is fixed: rule arrays and rule objects, then the lookup, then closures, whatever order `validateUsing()` was called in. The first failure stops the pipeline.
- `record_not_found` text is exactly `No record found for ":code".` (en).
- Submit-time rejections show the normal field error only. `onRejected` and `ScanRejected` are live-scan only and don't exist until PR 3.
- A disabled or read-only field still rejects with `scan_rejected` (PR 1 behaviour, unchanged).
- Cooldown default: `qr-code.scan.cooldown_ms` (1500) when `hasServerScanChecks()` is true, otherwise `0`. An explicit `cooldown()` value always wins, clamped to `>= 0`.
- Out of scope here: `onScanned()`/`onRejected()`, events and `clearOnReject()` (PR 3); browser payload keys `cooldownMs`, `clearOnReject`, `scanFailedMessage` and all JS (PR 4); feedback (PR 5).
- TDD: every task starts with its named failing test, run and seen to fail for the stated reason.
- Verification commands: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`.

## Files

| File | Responsibility |
|---|---|
| Modify `src/Concerns/InteractsWithScans.php` | `validateUsing()`, `lookup()`, `cooldown()`, `getCooldownMs()`, pipeline stages in `runScanChecks()`, `hasServerScanChecks()` |
| Create `src/Validation/ScanChecksRule.php` | Submit-time `ValidationRule` that calls `runScanChecks()` |
| Modify `config/qr-code.php` | Add `scan.cooldown_ms` |
| Modify `resources/lang/en/ui.php`, `resources/lang/id/ui.php` | `record_not_found`, `scan_failed` |
| Create `tests/Feature/ScanChecksTest.php` | Fixture model, configurable Livewire form, pipeline tests |
| Create `tests/Feature/ScanChecksSubmitTest.php` | Submit-time tests |
| Create `tests/Unit/ScanCooldownTest.php` | Cooldown default rule |

## Shared test fixture

Tasks 1–6 share two fixture classes under `tests/Fixtures/`, autoloaded by the existing `Mmuqiitf\FilamentQrCode\Tests\` PSR-4 root (`composer.json` autoload-dev). Classes with static helpers, rather than global functions in a test file, let both test files use them without `require`. Task 1 creates them.

`tests/Fixtures/ScanCheckProduct.php`:

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScanCheckProduct extends Model
{
    protected $table = 'scan_check_products';

    protected $guarded = [];

    public $timestamps = false;

    public static function createTableWithProducts(): void
    {
        Schema::dropIfExists('scan_check_products');
        Schema::create('scan_check_products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('stock')->default(0);
        });

        self::create(['sku' => 'SKU-1', 'active' => true, 'stock' => 5]);
        self::create(['sku' => 'SKU-OLD', 'active' => false, 'stock' => 5]);
        self::create(['sku' => 'SKU-EMPTY', 'active' => true, 'stock' => 0]);
    }
}
```

`tests/Fixtures/ScanChecksTestComponent.php`:

```php
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
```

Both test files (`tests/Feature/ScanChecksTest.php`, `tests/Feature/ScanChecksSubmitTest.php`) start with:

```php
<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);
```

The key `form.sku` was verified at runtime in PR 1.

---

### Task 1: `validateUsing()` with rule arrays and rule objects

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Create: `tests/Fixtures/ScanCheckProduct.php`, `tests/Fixtures/ScanChecksTestComponent.php` (shared fixture above)
- Test: `tests/Feature/ScanChecksTest.php` (create, starting with the shared header)

**Interfaces:**
- Consumes: PR 1 `runScanChecks(string $code, ?ScanInput $source): ScanResult`, `ScanResult::accept()/reject()`.
- Produces:
  - `public function validateUsing(array|ValidationRule|Closure $rules): static` (appends; arrays and rule objects go to `protected array $scanRules`, closures to `protected array $scanCheckClosures`, used in Task 3)
  - `protected function checkScanRules(string $code, ?ScanInput $source): ?ScanResult` (returns a rejection, or `null` to continue)
  - `hasServerScanChecks()` returns `true` when `$scanRules` or `$scanCheckClosures` is non-empty or a lookup is set (lookup added in Task 2).

- [ ] **Step 1: Write the fixture and the failing tests** (both fixture files, the shared header, then these tests)

First failing test: **"handleScan returns passes=false with translated message when rule fails"**.

```php
it('handleScan returns passes=false with translated message when rule fails', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(['string', 'max:5']));

    ScanChecksTestComponent::scan($test, 'TOO-LONG-CODE')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'The Sku field must not be greater than 5 characters.')
        ->assertSet('data.sku', null);
});

it('accepts a code that passes every rule', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(['string', 'max:10']));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true)
        ->assertSet('data.sku', 'SKU-1');
});

it('accepts a rule object and merges repeated validateUsing calls', function (): void {
    $startsWithSku = new class implements \Illuminate\Contracts\Validation\ValidationRule
    {
        public function validate(string $attribute, mixed $value, \Closure $fail): void
        {
            if (! str_starts_with((string) $value, 'SKU-')) {
                $fail('Codes must start with SKU-.');
            }
        }
    };

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->validateUsing(['max:10'])
        ->validateUsing($startsWithSku));

    ScanChecksTestComponent::scan($test, 'ABC')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'Codes must start with SKU-.');

    ScanChecksTestComponent::scan($test, 'SKU-123456789')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'The Sku field must not be greater than 10 characters.');
});

it('reports server checks once a rule is configured', function (): void {
    expect(QrScanner::make('sku')->hasServerScanChecks())->toBeFalse()
        ->and(QrScanner::make('sku')->validateUsing(['max:5'])->hasServerScanChecks())->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php`
Expected: FAIL with `BadMethodCallException: Method ...QrScanner::validateUsing does not exist`.

- [ ] **Step 3: Write minimal implementation**

Add to `InteractsWithScans` (imports: `Closure`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Support\Facades\Validator`):

```php
    /** @var array<int, mixed> */
    protected array $scanRules = [];

    /** @var array<int, Closure> */
    protected array $scanCheckClosures = [];

    /** @param  array<int, mixed>|ValidationRule|Closure  $rules */
    public function validateUsing(array|ValidationRule|Closure $rules): static
    {
        if ($rules instanceof Closure) {
            $this->scanCheckClosures[] = $rules;

            return $this;
        }

        $this->scanRules = [...$this->scanRules, ...(is_array($rules) ? $rules : [$rules])];

        return $this;
    }

    protected function checkScanRules(string $code, ?ScanInput $source): ?ScanResult
    {
        if ($this->scanRules === []) {
            return null;
        }

        $validator = Validator::make(
            ['code' => $code],
            ['code' => $this->scanRules],
            attributes: ['code' => $this->getLabel()],
        );

        if ($validator->passes()) {
            return null;
        }

        return ScanResult::reject($code, $source, (string) $validator->errors()->first('code'));
    }
```

Replace `runScanChecks()` and `hasServerScanChecks()`:

```php
    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        if ($code === '') {
            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_empty'));
        }

        return $this->checkScanRules($code, $source) ?? ScanResult::accept($code, $source);
    }

    public function hasServerScanChecks(): bool
    {
        return $this->scanRules !== []
            || $this->scanCheckClosures !== [];
    }
```

`getLabel()` may return an `Htmlable`; if PHPStan flags it, cast with `(string)` and record a ledger ruling.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Concerns/InteractsWithScans.php tests/Fixtures tests/Feature/ScanChecksTest.php
git commit -m "feat: validate live scans with validateUsing rules"
```

---

### Task 2: `lookup()` and `record_not_found`

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Modify: `resources/lang/en/ui.php`, `resources/lang/id/ui.php`
- Test: `tests/Feature/ScanChecksTest.php` (append)

**Interfaces:**
- Consumes: Task 1 `checkScanRules()`, `hasServerScanChecks()`.
- Produces:
  - `public function lookup(string $model, string $column = 'code', ?Closure $modifyQueryUsing = null): static` (`$model` is `class-string<Model>`; a second call replaces the first)
  - `protected function lookUpScanRecord(string $code, ?ScanInput $source): Model|ScanResult|null` (`null` when no lookup is configured, a `Model` on hit, a rejection on miss)
  - Translation key `filament-qr-code::ui.record_not_found` with `:code`.

- [ ] **Step 1: Write the failing tests**

First failing test: **"lookup sets record on ScanResult; missing SKU rejects with record_not_found"**.

```php
it('lookup sets record on ScanResult; missing SKU rejects with record_not_found', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true
            && $result['record_key'] === ScanCheckProduct::query()->where('sku', 'SKU-1')->value('id'));

    ScanChecksTestComponent::scan($test, 'SKU-404')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'No record found for "SKU-404".');
});

it('translates record_not_found', function (): void {
    app()->setLocale('id');
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-404')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'Data untuk "SKU-404" tidak ditemukan.');
});

it('applies modifyQueryUsing to the lookup', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(
        ScanCheckProduct::class,
        'sku',
        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('active', true),
    ));

    ScanChecksTestComponent::scan($test, 'SKU-OLD')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false);
});

it('runs rules before the lookup', function (): void {
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->validateUsing(['max:3']));

    \Illuminate\Support\Facades\DB::flushQueryLog();

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => str_contains((string) $result['message'], 'must not be greater than 3'));

    expect(collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query')->filter(fn (string $sql): bool => str_contains($sql, 'scan_check_products')))->toBeEmpty();
});

it('reports server checks once a lookup is configured', function (): void {
    expect(QrScanner::make('sku')->lookup(ScanCheckProduct::class, 'sku')->hasServerScanChecks())->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php --filter=lookup`
Expected: FAIL with `BadMethodCallException: Method ...QrScanner::lookup does not exist`.

- [ ] **Step 3: Add translations**

`resources/lang/en/ui.php` (append before `];`):

```php
    'record_not_found' => 'No record found for ":code".',
```

`resources/lang/id/ui.php`:

```php
    'record_not_found' => 'Data untuk ":code" tidak ditemukan.',
```

- [ ] **Step 4: Write minimal implementation**

Add to `InteractsWithScans` (imports: `Illuminate\Database\Eloquent\Builder`, `Illuminate\Database\Eloquent\Model`):

```php
    /** @var class-string<Model>|null */
    protected ?string $lookupModel = null;

    protected string $lookupColumn = 'code';

    protected ?Closure $modifyLookupQueryUsing = null;

    /** @param  class-string<Model>  $model */
    public function lookup(string $model, string $column = 'code', ?Closure $modifyQueryUsing = null): static
    {
        $this->lookupModel = $model;
        $this->lookupColumn = $column;
        $this->modifyLookupQueryUsing = $modifyQueryUsing;

        return $this;
    }

    protected function lookUpScanRecord(string $code, ?ScanInput $source): Model|ScanResult|null
    {
        if ($this->lookupModel === null) {
            return null;
        }

        $query = $this->lookupModel::query();

        if ($this->modifyLookupQueryUsing instanceof Closure) {
            $query = $this->evaluate($this->modifyLookupQueryUsing, ['query' => $query], [Builder::class => $query]) ?? $query;
        }

        $record = $query->where($this->lookupColumn, $code)->first();

        if ($record instanceof Model) {
            return $record;
        }

        return ScanResult::reject($code, $source, __('filament-qr-code::ui.record_not_found', ['code' => $code]));
    }
```

Replace `runScanChecks()` and `hasServerScanChecks()`:

```php
    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        if ($code === '') {
            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_empty'));
        }

        if ($rejection = $this->checkScanRules($code, $source)) {
            return $rejection;
        }

        $record = $this->lookUpScanRecord($code, $source);

        if ($record instanceof ScanResult) {
            return $record;
        }

        return ScanResult::accept($code, $source, $record);
    }

    public function hasServerScanChecks(): bool
    {
        return $this->scanRules !== []
            || $this->scanCheckClosures !== []
            || $this->lookupModel !== null;
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php`
Expected: PASS (9 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Concerns/InteractsWithScans.php resources/lang tests/Feature/ScanChecksTest.php
git commit -m "feat: look up scanned codes with lookup()"
```

---

### Task 3: Closure checks and pipeline order

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Test: `tests/Feature/ScanChecksTest.php` (append)

**Interfaces:**
- Consumes: Task 1 `$scanCheckClosures`, Task 2 `lookUpScanRecord()`.
- Produces: `protected function runScanCheckClosures(string $code, ?ScanInput $source, ?Model $record): ?ScanResult`. Closures are evaluated with named injections `code`, `record` (the lookup result, or `null`), `source`, plus Filament's defaults (`get`, `set`, `component`, `livewire`, `state`). A closure's return: `null` or `true` passes; a non-empty string fails with that message; `false` fails with `scan_rejected`; anything else passes.

Note: inject the lookup result by the **parameter name `$record`**. Filament resolves a bare `Model`-typed parameter to the form's own record, so closures must name the parameter `$record` to receive the lookup result. This is documented in the PR 2 docs commit (Task 6 step 7).

- [ ] **Step 1: Write the failing tests**

First failing test: **"closure check receives the looked-up record and its message rejects the scan"**.

```php
it('closure check receives the looked-up record and its message rejects the scan', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->validateUsing(fn (string $code, ?ScanCheckProduct $record): ?string => $record?->stock > 0 ? null : 'Out of stock'));

    ScanChecksTestComponent::scan($test, 'SKU-EMPTY')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === 'Out of stock');

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true);
});

it('rejects with scan_rejected when a closure returns false', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(fn (): bool => false));

    ScanChecksTestComponent::scan($test, 'ANY')
        ->assertReturned(fn (array $result): bool => $result['message'] === __('filament-qr-code::ui.scan_rejected'));
});

it('passes the scan source to closures', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->validateUsing(fn (?\Mmuqiitf\FilamentQrCode\Enums\ScanInput $source): ?string => $source === \Mmuqiitf\FilamentQrCode\Enums\ScanInput::Hardware ? null : 'Hardware only'));

    ScanChecksTestComponent::scan($test, 'X', 'camera')->assertReturned(fn (array $result): bool => $result['message'] === 'Hardware only');
    ScanChecksTestComponent::scan($test, 'X', 'hardware')->assertReturned(fn (array $result): bool => $result['passes'] === true);
});

it('runs rules, then lookup, then closures and stops at the first failure', function (): void {
    $calls = [];

    $test = ScanChecksTestComponent::test(function (QrScanner $scanner) use (&$calls) {
        return $scanner
            ->validateUsing(function () use (&$calls): ?string {
                $calls[] = 'closure-1';

                return 'First closure fails';
            })
            ->validateUsing(function () use (&$calls): ?string {
                $calls[] = 'closure-2';

                return null;
            })
            ->lookup(ScanCheckProduct::class, 'sku')
            ->validateUsing(['max:10']);
    });

    $calls = [];
    ScanChecksTestComponent::scan($test, 'SKU-404')->assertReturned(fn (array $result): bool => str_starts_with((string) $result['message'], 'No record found'));
    expect($calls)->toBe([]);

    $calls = [];
    ScanChecksTestComponent::scan($test, 'SKU-1')->assertReturned(fn (array $result): bool => $result['message'] === 'First closure fails');
    expect($calls)->toBe(['closure-1']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php --filter="closure"`
Expected: FAIL: "closure check receives the looked-up record…" returns `passes => true` for `SKU-EMPTY` because closures are stored but never run.

- [ ] **Step 3: Write minimal implementation**

Add to `InteractsWithScans`:

```php
    protected function runScanCheckClosures(string $code, ?ScanInput $source, ?Model $record): ?ScanResult
    {
        foreach ($this->scanCheckClosures as $check) {
            $outcome = $this->evaluate($check, [
                'code' => $code,
                'record' => $record,
                'source' => $source,
            ], [
                ScanInput::class => $source,
            ]);

            if ($outcome === false) {
                return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_rejected'));
            }

            if (is_string($outcome) && $outcome !== '') {
                return ScanResult::reject($code, $source, $outcome);
            }
        }

        return null;
    }
```

In `runScanChecks()`, replace the final `return ScanResult::accept($code, $source, $record);` with:

```php
        return $this->runScanCheckClosures($code, $source, $record)
            ?? ScanResult::accept($code, $source, $record);
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php`
Expected: PASS (13 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Concerns/InteractsWithScans.php tests/Feature/ScanChecksTest.php
git commit -m "feat: run closure scan checks after the lookup"
```

---

### Task 4: Exceptions become `scan_failed`

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Modify: `resources/lang/en/ui.php`, `resources/lang/id/ui.php`
- Test: `tests/Feature/ScanChecksTest.php` (append)

**Interfaces:**
- Consumes: Task 3 `runScanChecks()`.
- Produces: `runScanChecks()` never throws. A `ValidationException` thrown by a check becomes a rejection with its first message and is **not** reported. Any other `Throwable` is passed to `report()` and becomes `scan_failed`. Translation key `filament-qr-code::ui.scan_failed`.

- [ ] **Step 1: Write the failing tests**

First failing test: **"a throwing check becomes scan_failed and is reported"**.

```php
it('a throwing check becomes scan_failed and is reported', function (): void {
    \Illuminate\Support\Facades\Exceptions::fake();

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(fn () => throw new \RuntimeException('boom')));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'The scan could not be checked. Please try again.')
        ->assertSet('data.sku', null);

    \Illuminate\Support\Facades\Exceptions::assertReported(\RuntimeException::class);
});

it('converts a ValidationException into its message without reporting it', function (): void {
    \Illuminate\Support\Facades\Exceptions::fake();

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(
        fn () => throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'Blocked code']),
    ));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'Blocked code');

    \Illuminate\Support\Facades\Exceptions::assertNothingReported();
});
```

`Exceptions::fake()` exists from Laravel 11. If the Testbench app in use lacks it, replace it with a bound `ExceptionHandler` spy and record a ledger ruling.

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php --filter="scan_failed|ValidationException"`
Expected: FAIL: the `RuntimeException('boom')` escapes `callSchemaComponentMethod` and the test errors with `RuntimeException: boom`.

- [ ] **Step 3: Add translations**

en: `    'scan_failed' => 'The scan could not be checked. Please try again.',`
id: `    'scan_failed' => 'Pindaian tidak dapat diperiksa. Silakan coba lagi.',`

- [ ] **Step 4: Write minimal implementation**

Rename the current body of `runScanChecks()` to `protected function runScanCheckStages(string $code, ?ScanInput $source): ScanResult` and add (imports: `Illuminate\Validation\ValidationException`; `Throwable` is already imported):

```php
    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        try {
            return $this->runScanCheckStages($code, $source);
        } catch (ValidationException $exception) {
            return ScanResult::reject($code, $source, (string) collect($exception->errors())->flatten()->first());
        } catch (Throwable $exception) {
            report($exception);

            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_failed'));
        }
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanChecksTest.php`
Expected: PASS (15 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Concerns/InteractsWithScans.php resources/lang tests/Feature/ScanChecksTest.php
git commit -m "feat: turn failing scan checks into scan_failed rejections"
```

---

### Task 5: Cooldown default rule

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Modify: `config/qr-code.php`
- Test: `tests/Unit/ScanCooldownTest.php`

**Interfaces:**
- Consumes: `hasServerScanChecks()` (Tasks 1–2), `Support\ConfigValue::int(string $key, int $default, int $minimum)`.
- Produces: `public function cooldown(int|Closure $milliseconds): static`; `public function getCooldownMs(): int`. The browser payload key `cooldownMs` is added in PR 4, not here.

- [ ] **Step 1: Write the failing test**

First failing test: **"cooldown defaults to 0 without checks and to scan.cooldown_ms with checks"**.

```php
<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

it('cooldown defaults to 0 without checks and to scan.cooldown_ms with checks', function (): void {
    expect(QrScanner::make('sku')->getCooldownMs())->toBe(0)
        ->and(QrScanner::make('sku')->validateUsing(['max:5'])->getCooldownMs())->toBe(1500);

    config()->set('qr-code.scan.cooldown_ms', 900);

    expect(QrScanner::make('sku')->validateUsing(['max:5'])->getCooldownMs())->toBe(900);
});

it('lets an explicit cooldown win and clamps negatives to 0', function (): void {
    expect(QrScanner::make('sku')->cooldown(700)->getCooldownMs())->toBe(700)
        ->and(QrScanner::make('sku')->validateUsing(['max:5'])->cooldown(0)->getCooldownMs())->toBe(0)
        ->and(QrScanner::make('sku')->cooldown(-50)->getCooldownMs())->toBe(0)
        ->and(QrScanner::make('sku')->cooldown(fn (): int => 300)->getCooldownMs())->toBe(300);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/ScanCooldownTest.php`
Expected: FAIL with `BadMethodCallException: Method ...QrScanner::getCooldownMs does not exist`.

- [ ] **Step 3: Add config**

In `config/qr-code.php`, add after the `feedback` block:

```php
    /*
    |--------------------------------------------------------------------------
    | Live Scan Checks
    |--------------------------------------------------------------------------
    |
    | - cooldown_ms: Same code scanned again within this window is ignored.
    |   Used only by fields with server checks (validateUsing / lookup);
    |   fields without checks default to 0. Override per field with cooldown().
    |
    */
    'scan' => [
        'cooldown_ms' => 1500,
    ],
```

- [ ] **Step 4: Write minimal implementation**

Add to `InteractsWithScans` (import `Mmuqiitf\FilamentQrCode\Support\ConfigValue`):

```php
    protected int|Closure|null $cooldownMs = null;

    public function cooldown(int|Closure $milliseconds): static
    {
        $this->cooldownMs = $milliseconds;

        return $this;
    }

    public function getCooldownMs(): int
    {
        if ($this->cooldownMs !== null) {
            return max(0, (int) $this->evaluate($this->cooldownMs));
        }

        if (! $this->hasServerScanChecks()) {
            return 0;
        }

        return ConfigValue::int('qr-code.scan.cooldown_ms', 1500, minimum: 0);
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/ScanCooldownTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Concerns/InteractsWithScans.php config/qr-code.php tests/Unit/ScanCooldownTest.php
git commit -m "feat: add cooldown() with a checks-aware default"
```

---

### Task 6: `ScanChecksRule` re-runs the checks on submit

**Files:**
- Create: `src/Validation/ScanChecksRule.php`
- Modify: `src/Concerns/InteractsWithScans.php` (register the rule once, from `validateUsing()` and `lookup()`)
- Modify: `docs/scanning.md` (document `validateUsing()`, `lookup()`, `cooldown()` and the `$record` naming rule)
- Test: `tests/Feature/ScanChecksSubmitTest.php`

**Interfaces:**
- Consumes: `runScanChecks()` (Task 4); fixtures `Tests\Fixtures\ScanChecksTestComponent::test()` and `Tests\Fixtures\ScanCheckProduct::createTableWithProducts()` (Task 1).
- Produces: `final readonly class Mmuqiitf\FilamentQrCode\Validation\ScanChecksRule implements ValidationRule` with `__construct(private QrScanner $component)`; `protected function registerScanChecksRule(): void` on the trait.

Design note (deviation from spec 4.4 wording "registers one extra field rule in `setUp()`"): the rule is registered lazily the first time `validateUsing()` or `lookup()` is called, guarded by a flag. This avoids adding a `setUp()` to `QrScanner` and keeps fields without checks free of the rule. The behaviour matches the spec.

- [ ] **Step 1: Write the failing tests**

First failing test: **"submit with an unknown SKU fails with record_not_found"**.

```php
<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);

it('submit with an unknown SKU fails with record_not_found', function (): void {
    ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'))
        ->set('data.sku', 'SKU-404')
        ->call('submit')
        ->assertHasErrors(['data.sku' => ['No record found for "SKU-404".']])
        ->assertSet('submitted', false);
});

it('submit re-runs rules and closures with the same messages', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->validateUsing(['max:10'])
        ->lookup(ScanCheckProduct::class, 'sku')
        ->validateUsing(fn (?ScanCheckProduct $record): ?string => $record?->stock > 0 ? null : 'Out of stock'));

    $test->set('data.sku', 'SKU-1234567890')->call('submit')->assertHasErrors(['data.sku']);
    expect($test->errors()->get('data.sku'))->toBe(['The Sku field must not be greater than 10 characters.']);

    $test->set('data.sku', 'SKU-EMPTY')->call('submit');
    expect($test->errors()->get('data.sku'))->toBe(['Out of stock']);

    $test->set('data.sku', 'SKU-1')->call('submit')->assertHasNoErrors()->assertSet('submitted', true);
});

it('lets a blank value through when the field is not required', function (): void {
    ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);
});

it('adds no submit rule when no checks are configured', function (): void {
    ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner)
        ->set('data.sku', 'anything')
        ->call('submit')
        ->assertHasNoErrors();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanChecksSubmitTest.php`
Expected: "submit with an unknown SKU…" FAILS with `Component has no errors` (nothing runs the lookup at submit). "submit re-runs rules…" FAILS on the `SKU-EMPTY` step. The last two tests pass already and stay as regression guards.

- [ ] **Step 3: Write the rule**

`src/Validation/ScanChecksRule.php`:

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

final readonly class ScanChecksRule implements ValidationRule
{
    public function __construct(
        private QrScanner $component,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $result = $this->component->runScanChecks(trim((string) $value), source: null);

        if ($result->passes) {
            return;
        }

        $fail((string) $result->message);
    }
}
```

- [ ] **Step 4: Register the rule once**

Add to `InteractsWithScans` (import `Mmuqiitf\FilamentQrCode\Validation\ScanChecksRule`):

```php
    protected bool $hasRegisteredScanChecksRule = false;

    protected function registerScanChecksRule(): void
    {
        if ($this->hasRegisteredScanChecksRule) {
            return;
        }

        $this->hasRegisteredScanChecksRule = true;

        $this->rule(fn (): ScanChecksRule => new ScanChecksRule($this));
    }
```

Call `$this->registerScanChecksRule();` as the first line of `validateUsing()` and of `lookup()`.

`ScanChecksRule` type-hints `QrScanner`. The trait is only used by `QrScanner`; if PHPStan flags `$this` as the trait type, add `/** @var QrScanner $this */` inside `registerScanChecksRule()` and record a ruling.

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanChecksSubmitTest.php tests/Feature/ScanChecksTest.php`
Expected: PASS (all).

- [ ] **Step 6: Prove a failing rule reports one error, not two**

The "submit re-runs rules…" test asserts `toBe([...one message...])` for the rule failure. If it shows two messages, `validateUsing()` is also adding plain field rules somewhere; remove that. The spec forbids duplicates.

- [ ] **Step 7: Document the API**

Append a "Server-side checks" section to `docs/scanning.md`:

````markdown
## Server-side checks

```php
QrScanner::make('sku')
    ->validateUsing(['required', 'string', 'max:32'])
    ->lookup(Product::class, 'sku', modifyQueryUsing: fn (Builder $query) => $query->where('active', true))
    ->validateUsing(fn (string $code, ?Product $record): ?string => $record?->stock > 0 ? null : __('Out of stock'))
    ->cooldown(1500);
```

- Checks run on every live scan and again when the form is submitted, always in this order: rules, lookup, closures. The first failure stops the scan.
- A closure returns `null` or `true` to pass, a message to fail, or `false` to fail with the generic message.
- Name the closure parameter `$record` to receive the lookup result. A `Model`-typed parameter with another name receives the form's record instead.
- Fields with checks ignore a repeated code for `qr-code.scan.cooldown_ms` (1500 ms) by default; fields without checks don't. Use `cooldown(0)` to turn it off.
````

- [ ] **Step 8: Full verification**

Run each, fresh: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`.
Expected: all exit 0; no snapshot changes (the `ScanPayloadCharacterizationTest` scanner has no checks, so `serverCheck` stays `false`).

- [ ] **Step 9: Commit**

```bash
git add src/Validation/ScanChecksRule.php src/Concerns/InteractsWithScans.php tests/Feature/ScanChecksSubmitTest.php docs/scanning.md
git commit -m "feat: re-run scan checks on form submit with ScanChecksRule"
```

---

## Spec coverage

| Spec requirement | Task |
|---|---|
| `validateUsing()` arrays, rule objects, closures, repeatable | 1, 3 |
| `lookup(Model, column, modifyQueryUsing:)`, `record_not_found` | 2 |
| Order rules → lookup → closures, first failure stops | 2, 3 |
| Closure return contract and injections | 3 |
| Exceptions reported → `scan_failed`; `ValidationException` not reported | 4 |
| Cooldown default rule (0 without checks, 1500 with) | 5 |
| `ScanChecksRule` on submit; blank passes; one error; no callbacks/events | 6 |
| `hasServerScanChecks()` true for any configured check | 1, 2 |
| Payload `cooldownMs`, client dedupe | PR 4 (out of scope) |
