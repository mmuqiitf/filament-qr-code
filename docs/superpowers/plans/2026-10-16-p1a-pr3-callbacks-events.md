# P1a PR 3: Scan Callbacks and Events Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let apps react to live scans: `onScanned()`/`onRejected()` callbacks with `Get`/`Set`, `clearOnReject()`, and the `ScanAccepted`/`ScanRejected` events, with `QrCodeScanned` fired for accepted live scans so the existing audit listener sees them.

**Architecture:** All side effects stay in `InteractsWithScans::handleScan()`, which already calls `runScanChecks()` (pure checks, shared with the submit rule). PR 3 adds two private steps to `handleScan()`: `acceptScan()` (state, `afterStateUpdated`, `onScanned`, events) and `rejectScan()` (optional clear, `onRejected`, event). `ScanChecksRule` keeps calling only `runScanChecks()`, so nothing in this PR can run at submit. The server never dispatches the `qr-scan-rejected` browser event for `handleScan`; the browser dispatches it once from the result (PR 4).

**Tech Stack:** PHP 8.2+, Laravel 11–13 events, Filament v5 (`evaluate()`, `Get`/`Set` utilities), Livewire 3, Pest with `Event::fake()` and `Exceptions::fake()`.

**Spec:** `docs/superpowers/specs/2026-10-16-p1a-server-scan-validation-design.md`, sections 4.1 (events), 4.2 (`onScanned`, `onRejected`, `clearOnReject`, callback injection), 4.3 steps 7–8, 4.4 (submit never runs callbacks or events), 4.5, 6 (`scan.clear_on_reject`), 9 item 4, and row 3 of section 8.

## Global Constraints

- PHP files start with `declare(strict_types=1);`. Follow `.agents/skills/spatie-laravel-php`: typed properties and returns, constructor promotion, early returns, no docblocks that repeat type hints. Events are plain final classes, like `Events\QrCodeScanned`.
- Callbacks and events run for **live scans only** (`handleScan()`), never at submit (`ScanChecksRule`).
- Order on pass: `state($code)` → `callAfterStateUpdated()` → `onScanned` → `ScanAccepted` → `QrCodeScanned`.
- Order on fail: `state(null)` if `clearOnReject` → `onRejected` → `ScanRejected`.
- `QrCodeScanned` keeps its constructor. Live scans pass `source: $result->source->value` (`camera`, `hardware` or `upload`), `field: $this->getName()`, `context: ['record_key' => ...]`. `triggerOnScan()` keeps `source: 'scanner-field'` and is untouched.
- `handleScan()` never dispatches the Livewire/browser event `qr-scan-rejected`. `rejectWhen()` is unchanged and keeps dispatching it from its own `afterStateUpdated` hook.
- `clearOnReject` default comes from `qr-code.scan.clear_on_reject` (`true`).
- `hasServerScanChecks()` also becomes true when `onScanned()` or `onRejected()` is set (spec 4.2). Callbacks don't register `ScanChecksRule` (they have nothing to check at submit).
- Out of scope: all JS, the browser payload keys `clearOnReject`/`cooldownMs`/`scanFailedMessage` (PR 4), feedback (PR 5), renaming `LogQrCodeScan` (not in this sub-project).
- TDD: each task starts with its named failing test, run and seen to fail for the stated reason.
- Verification: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`.

## Files

| File | Responsibility |
|---|---|
| Create `src/Events/ScanAccepted.php`, `src/Events/ScanRejected.php` | Live-scan outcome events |
| Modify `src/Concerns/InteractsWithScans.php` | `onScanned()`, `onRejected()`, `clearOnReject()`, `shouldClearOnReject()`, `acceptScan()`, `rejectScan()`, `evaluateScanCallback()`, `hasServerScanChecks()` |
| Modify `config/qr-code.php` | `scan.clear_on_reject` |
| Modify `docs/scanning.md` | Callbacks, events, audit note |
| Create `tests/Feature/ScanCallbacksTest.php` | All PR 3 behaviour, using the PR 2 fixtures |

## Existing behaviour this PR builds on (`LogQrCodeScan`)

`FilamentQrCodeServiceProvider` registers `Event::listen(QrCodeScanned::class, LogQrCodeScan::class)`. The listener logs `filament-qr-code.scan` with `code`, `source`, `field` and `context`, only when `qr-code.audit.enabled` is true, to `qr-code.audit.channel` or the default log. Implications recorded here and tested in Task 4:

1. Once PR 3 lands, **every accepted live scan is audited** when auditing is on, with `source` `camera`, `hardware` or `upload` (not `scanner-field`) and `context.record_key`. This closes gap A.3-1 / the "live scans aren't audited" note in the config comment, which this PR updates.
2. **Rejected scans are not audited**, because only `QrCodeScanned` is logged and it fires on accept only. Apps that need rejection audits listen to `ScanRejected`.
3. With auditing off (the default), nothing new is logged.
4. The listener is synchronous; it runs inside the `handleScan` request. It's cheap (one log call), so no queueing is added.

## Shared fixture

Reuse `tests/Fixtures/ScanChecksTestComponent.php` and `tests/Fixtures/ScanCheckProduct.php` from PR 2 unchanged. The test file header:

```php
<?php

declare(strict_types=1);

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Event;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);
```

---

### Task 1: `ScanAccepted`, `ScanRejected` and live `QrCodeScanned`

**Files:**
- Create: `src/Events/ScanAccepted.php`, `src/Events/ScanRejected.php`
- Modify: `src/Concerns/InteractsWithScans.php`
- Test: `tests/Feature/ScanCallbacksTest.php` (create with the header above)

**Interfaces:**
- Consumes: `ScanResult` (PR 1), `runScanChecks()` (PR 2), `Events\QrCodeScanned(string $code, string $source, ?string $field = null, array $context = [])`.
- Produces:
  - `final class ScanAccepted { __construct(public ScanResult $result, public string $field) }`
  - `final class ScanRejected { __construct(public ScanResult $result, public string $field) }`
  - `protected function acceptScan(ScanResult $result): void` and `protected function rejectScan(ScanResult $result): void` on the trait; Tasks 2–4 extend them.

- [ ] **Step 1: Write the failing tests**

First failing test: **"accepted scan dispatches ScanAccepted and QrCodeScanned with source=hardware"**.

```php
it('accepted scan dispatches ScanAccepted and QrCodeScanned with source=hardware', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);
    $productId = ScanCheckProduct::query()->where('sku', 'SKU-1')->value('id');

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-1', 'hardware');

    Event::assertDispatched(ScanAccepted::class, fn (ScanAccepted $event): bool => $event->field === 'sku'
        && $event->result->code === 'SKU-1'
        && $event->result->record?->getKey() === $productId);
    Event::assertDispatched(QrCodeScanned::class, fn (QrCodeScanned $event): bool => $event->code === 'SKU-1'
        && $event->source === 'hardware'
        && $event->field === 'sku'
        && $event->context === ['record_key' => $productId]);
    Event::assertNotDispatched(ScanRejected::class);
});

it('rejected scan dispatches ScanRejected only', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-404', 'camera');

    Event::assertDispatched(ScanRejected::class, fn (ScanRejected $event): bool => $event->field === 'sku'
        && $event->result->message === 'No record found for "SKU-404".'
        && $event->result->source?->value === 'camera');
    Event::assertNotDispatched(ScanAccepted::class);
    Event::assertNotDispatched(QrCodeScanned::class);
});

it('does not dispatch the qr-scan-rejected browser event from the server', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-404')->assertNotDispatched('qr-scan-rejected');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php`
Expected: the first two FAIL with `Class "Mmuqiitf\FilamentQrCode\Events\ScanAccepted" not found`. The third passes already and stays as a regression guard.

- [ ] **Step 3: Create the events**

`src/Events/ScanAccepted.php`:

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Events;

use Mmuqiitf\FilamentQrCode\Support\ScanResult;

final class ScanAccepted
{
    public function __construct(
        public ScanResult $result,
        public string $field,
    ) {}
}
```

`src/Events/ScanRejected.php`:

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Events;

use Mmuqiitf\FilamentQrCode\Support\ScanResult;

final class ScanRejected
{
    public function __construct(
        public ScanResult $result,
        public string $field,
    ) {}
}
```

- [ ] **Step 4: Dispatch from `handleScan()`**

Add imports `Mmuqiitf\FilamentQrCode\Events\QrCodeScanned`, `ScanAccepted`, `ScanRejected` to the trait. Replace the tail of `handleScan()` (from `$result = $this->runScanChecks(...)`) with:

```php
        $result = $this->runScanChecks($code, $input);

        if (! $result->passes) {
            $this->rejectScan($result);

            return $result->toClient();
        }

        $this->acceptScan($result);

        return $result->toClient();
    }

    protected function acceptScan(ScanResult $result): void
    {
        $this->state($result->code);
        $this->callAfterStateUpdated();

        event(new ScanAccepted($result, $this->getName()));
        event(new QrCodeScanned(
            code: $result->code,
            source: $result->source?->value ?? ScanInput::Camera->value,
            field: $this->getName(),
            context: ['record_key' => $result->record?->getKey()],
        ));
    }

    protected function rejectScan(ScanResult $result): void
    {
        event(new ScanRejected($result, $this->getName()));
    }
```

The disabled/read-only early return stays as it is (PR 1 behaviour) and dispatches nothing: the field isn't accepting scans, so it isn't a scan outcome.

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Events/ScanAccepted.php src/Events/ScanRejected.php src/Concerns/InteractsWithScans.php tests/Feature/ScanCallbacksTest.php
git commit -m "feat: dispatch scan outcome events for live scans"
```

---

### Task 2: `onScanned()` with `ScanResult`, `Get` and `Set`

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Test: `tests/Feature/ScanCallbacksTest.php` (append)

**Interfaces:**
- Consumes: Task 1 `acceptScan()`.
- Produces:
  - `public function onScanned(?Closure $callback): static`
  - `protected function evaluateScanCallback(?Closure $callback, ScanResult $result): void`. Named injections: `result`, `code`, `record`, `source`; typed injections: `ScanResult::class`, `ScanInput::class`; Filament supplies `get`/`set`/`Get`/`Set`, `component`, `livewire`.
  - `hasServerScanChecks()` also true when `$onScannedCallback !== null`.

- [ ] **Step 1: Write the failing tests**

First failing test: **"onScanned receives ScanResult, Set and Get and can fill a sibling field"**.

```php
it('onScanned receives ScanResult, Set and Get and can fill a sibling field', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->onScanned(function (ScanResult $result, Set $set, Get $get): void {
            $set('note', "{$get('sku')} x {$result->record->stock}");
        }));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertSet('data.sku', 'SKU-1')
        ->assertSet('data.note', 'SKU-1 x 5');
});

it('runs onScanned after state is set and before the events', function (): void {
    $order = [];
    Event::listen(ScanAccepted::class, function () use (&$order): void {
        $order[] = 'ScanAccepted';
    });
    Event::listen(QrCodeScanned::class, function () use (&$order): void {
        $order[] = 'QrCodeScanned';
    });

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->onScanned(function (Get $get) use (&$order): void {
            $order[] = "onScanned:{$get('sku')}";
        }));

    ScanChecksTestComponent::scan($test, 'ABC');

    expect($order)->toBe(['onScanned:ABC', 'ScanAccepted', 'QrCodeScanned']);
});

it('does not run onScanned for a rejected scan', function (): void {
    $calls = 0;

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->onScanned(function () use (&$calls): void {
            $calls++;
        }));

    ScanChecksTestComponent::scan($test, 'SKU-404');

    expect($calls)->toBe(0);
});

it('counts an onScanned callback as a server check', function (): void {
    expect(QrScanner::make('sku')->onScanned(fn () => null)->hasServerScanChecks())->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php --filter=onScanned`
Expected: FAIL with `BadMethodCallException: Method ...QrScanner::onScanned does not exist`.

- [ ] **Step 3: Write minimal implementation**

Add to the trait:

```php
    protected ?Closure $onScannedCallback = null;

    public function onScanned(?Closure $callback): static
    {
        $this->onScannedCallback = $callback;

        return $this;
    }

    protected function evaluateScanCallback(?Closure $callback, ScanResult $result): void
    {
        if (! $callback instanceof Closure) {
            return;
        }

        $this->evaluate($callback, [
            'result' => $result,
            'code' => $result->code,
            'record' => $result->record,
            'source' => $result->source,
        ], [
            ScanResult::class => $result,
            ScanInput::class => $result->source,
        ]);
    }
```

In `acceptScan()`, insert after `$this->callAfterStateUpdated();`:

```php
        $this->evaluateScanCallback($this->onScannedCallback, $result);
```

In `hasServerScanChecks()`, add `|| $this->onScannedCallback !== null`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Concerns/InteractsWithScans.php tests/Feature/ScanCallbacksTest.php
git commit -m "feat: add onScanned callback with Get and Set"
```

---

### Task 3: `onRejected()` and `clearOnReject()`

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Modify: `config/qr-code.php`
- Test: `tests/Feature/ScanCallbacksTest.php` (append)

**Interfaces:**
- Consumes: Task 1 `rejectScan()`, Task 2 `evaluateScanCallback()`.
- Produces:
  - `public function onRejected(?Closure $callback): static`
  - `public function clearOnReject(bool|Closure $condition = true): static`
  - `public function shouldClearOnReject(): bool` (PR 4 adds it to the browser payload)
  - `hasServerScanChecks()` also true when `$onRejectedCallback !== null`.

- [ ] **Step 1: Write the failing tests**

First failing test: **"rejected scan runs onRejected and clears state when clearOnReject"**.

```php
it('rejected scan runs onRejected and clears state when clearOnReject', function (): void {
    $messages = [];

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->onRejected(function (ScanResult $result, Set $set) use (&$messages): void {
            $messages[] = $result->message;
            $set('note', 'rejected');
        }));

    $test->set('data.sku', 'SKU-1');

    ScanChecksTestComponent::scan($test, 'SKU-404')
        ->assertSet('data.sku', null)
        ->assertSet('data.note', 'rejected');

    expect($messages)->toBe(['No record found for "SKU-404".']);
});

it('keeps the previous state when clearOnReject is off', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->clearOnReject(false));

    $test->set('data.sku', 'SKU-1');

    ScanChecksTestComponent::scan($test, 'SKU-404')->assertSet('data.sku', 'SKU-1');
});

it('reads the clearOnReject default from config', function (): void {
    expect(QrScanner::make('sku')->shouldClearOnReject())->toBeTrue();

    config()->set('qr-code.scan.clear_on_reject', false);

    expect(QrScanner::make('sku')->shouldClearOnReject())->toBeFalse()
        ->and(QrScanner::make('sku')->clearOnReject()->shouldClearOnReject())->toBeTrue();
});

it('runs onRejected before ScanRejected', function (): void {
    $order = [];
    Event::listen(ScanRejected::class, function () use (&$order): void {
        $order[] = 'ScanRejected';
    });

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->validateUsing(['max:2'])
        ->onRejected(function () use (&$order): void {
            $order[] = 'onRejected';
        }));

    ScanChecksTestComponent::scan($test, 'TOO-LONG');

    expect($order)->toBe(['onRejected', 'ScanRejected']);
});

it('counts an onRejected callback as a server check', function (): void {
    expect(QrScanner::make('sku')->onRejected(fn () => null)->hasServerScanChecks())->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php --filter="onRejected|clearOnReject"`
Expected: FAIL with `BadMethodCallException: Method ...QrScanner::onRejected does not exist` (and `clearOnReject`/`shouldClearOnReject` for the others).

- [ ] **Step 3: Add config**

In `config/qr-code.php`, extend the `scan` block and its comment:

```php
    | - clear_on_reject: Clear the field when a live scan is rejected.
    |   Override per field with clearOnReject().
    |
    */
    'scan' => [
        'cooldown_ms' => 1500,
        'clear_on_reject' => true,
    ],
```

- [ ] **Step 4: Write minimal implementation**

Add to the trait:

```php
    protected ?Closure $onRejectedCallback = null;

    protected bool|Closure|null $clearOnReject = null;

    public function onRejected(?Closure $callback): static
    {
        $this->onRejectedCallback = $callback;

        return $this;
    }

    public function clearOnReject(bool|Closure $condition = true): static
    {
        $this->clearOnReject = $condition;

        return $this;
    }

    public function shouldClearOnReject(): bool
    {
        if ($this->clearOnReject === null) {
            return ConfigValue::bool('qr-code.scan.clear_on_reject', true);
        }

        return (bool) $this->evaluate($this->clearOnReject);
    }
```

Replace `rejectScan()`:

```php
    protected function rejectScan(ScanResult $result): void
    {
        if ($this->shouldClearOnReject()) {
            $this->state(null);
        }

        $this->evaluateScanCallback($this->onRejectedCallback, $result);

        event(new ScanRejected($result, $this->getName()));
    }
```

In `hasServerScanChecks()`, add `|| $this->onRejectedCallback !== null`.

Clearing uses `state(null)` without `callAfterStateUpdated()`, so `normalizeUsing()`/`rejectWhen()` hooks don't see the clear.

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php`
Expected: PASS (12 tests).

- [ ] **Step 6: Commit**

```bash
git add src/Concerns/InteractsWithScans.php config/qr-code.php tests/Feature/ScanCallbacksTest.php
git commit -m "feat: add onRejected callback and clearOnReject"
```

---

### Task 4: Live-only guarantee, callback failures and audit

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php`
- Modify: `config/qr-code.php` (audit comment)
- Modify: `docs/scanning.md`
- Test: `tests/Feature/ScanCallbacksTest.php` (append)

**Interfaces:**
- Consumes: Tasks 1–3; `ScanChecksRule` (PR 2); `Listeners\LogQrCodeScan`.
- Produces: `acceptScan()` is wrapped so a `Throwable` from `callAfterStateUpdated()`, `onScanned` or a listener restores the previous state, is passed to `report()`, dispatches `ScanRejected` with `scan_failed`, and makes `handleScan()` return that rejection. `onRejected` isn't called for this failure, so a broken app callback can't loop.

- [ ] **Step 1: Write the failing tests**

First failing test: **"an exception in onScanned restores the previous state, is reported and returns scan_failed"**.

```php
it('an exception in onScanned restores the previous state, is reported and returns scan_failed', function (): void {
    \Illuminate\Support\Facades\Exceptions::fake();
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->onScanned(fn () => throw new \RuntimeException('callback broke')));

    $test->set('data.sku', 'OLD');

    ScanChecksTestComponent::scan($test, 'NEW')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === __('filament-qr-code::ui.scan_failed'))
        ->assertSet('data.sku', 'OLD');

    \Illuminate\Support\Facades\Exceptions::assertReported(\RuntimeException::class);
    Event::assertDispatched(ScanRejected::class);
    Event::assertNotDispatched(QrCodeScanned::class);
});

it('submit never runs onScanned/onRejected or dispatches scan events', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);
    $calls = [];

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->onScanned(function () use (&$calls): void {
            $calls[] = 'scanned';
        })
        ->onRejected(function () use (&$calls): void {
            $calls[] = 'rejected';
        }));

    $test->set('data.sku', 'SKU-404')->call('submit')->assertHasErrors(['data.sku']);
    $test->set('data.sku', 'SKU-1')->call('submit')->assertHasNoErrors();

    expect($calls)->toBe([]);
    Event::assertNotDispatched(ScanAccepted::class);
    Event::assertNotDispatched(ScanRejected::class);
    Event::assertNotDispatched(QrCodeScanned::class);
});

it('logs accepted live scans through LogQrCodeScan when auditing is enabled', function (): void {
    $logged = [];
    \Illuminate\Support\Facades\Log::listen(function (\Illuminate\Log\Events\MessageLogged $message) use (&$logged): void {
        $logged[] = $message->context;
    });
    config()->set('qr-code.audit.enabled', true);

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-1', 'upload');
    ScanChecksTestComponent::scan($test, 'SKU-404', 'upload');

    expect($logged)->toHaveCount(1)
        ->and($logged[0]['code'])->toBe('SKU-1')
        ->and($logged[0]['source'])->toBe('upload')
        ->and($logged[0]['field'])->toBe('sku')
        ->and($logged[0]['context'])->toBe(['record_key' => ScanCheckProduct::query()->where('sku', 'SKU-1')->value('id')]);

    config()->set('qr-code.audit.enabled', false);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php --filter="exception in onScanned"`
Expected: FAIL with `RuntimeException: callback broke` escaping `callSchemaComponentMethod`. The submit and audit tests pass already (they prove the live-only design and the existing listener wiring) and stay as regression guards. If either fails, stop: that's a real bug to fix before continuing.

- [ ] **Step 3: Write minimal implementation**

In `handleScan()`, replace `$this->acceptScan($result); return $result->toClient();` with:

```php
        $previousState = $this->getState();

        try {
            $this->acceptScan($result);
        } catch (Throwable $exception) {
            report($exception);

            $this->state($previousState);

            $failure = ScanResult::reject($result->code, $result->source, __('filament-qr-code::ui.scan_failed'));

            event(new ScanRejected($failure, $this->getName()));

            return $failure->toClient();
        }

        return $result->toClient();
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/ScanCallbacksTest.php`
Expected: PASS (15 tests).

- [ ] **Step 5: Update the audit config comment**

In `config/qr-code.php`, replace the sentence "live camera/hardware scans stay client-side and are not audited until they reach the server." with:

```php
    | Every server-observed scan (live scans accepted by a QrScanner with
    | server checks, collector action handling, programmatic triggers)
    | dispatches a QrCodeScanned event. Enable the log listener to record
    | them. Rejected scans fire ScanRejected and are not logged here.
```

- [ ] **Step 6: Document callbacks and events**

Append to the "Server-side checks" section of `docs/scanning.md`:

````markdown
### Reacting to scans

```php
QrScanner::make('sku')
    ->lookup(Product::class, 'sku')
    ->onScanned(function (ScanResult $result, Set $set): void {
        $set('price', $result->record->price);
    })
    ->onRejected(fn (ScanResult $result) => Notification::make()->danger()->title($result->message)->send())
    ->clearOnReject(); // default from qr-code.scan.clear_on_reject
```

- `onScanned` and `onRejected` run for live scans only, never when the form is submitted.
- Callbacks can ask for `ScanResult $result`, `Get $get`, `Set $set`, `string $code`, `$record` (named), `?ScanInput $source`, `$component` and `$livewire`.
- Accepted live scans fire `ScanAccepted` and `QrCodeScanned` (with `source` `camera`, `hardware` or `upload`); rejected ones fire `ScanRejected`. With `qr-code.audit.enabled`, accepted live scans are logged.
- If `onScanned` throws, the field keeps its previous value, the error is reported, and the scan is rejected with the generic message.
- Configuring `onScanned` or `onRejected` alone makes the field call the server on every scan and enables the default cooldown.
````

- [ ] **Step 7: Full verification**

Run each, fresh: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`.
Expected: all exit 0. `ScanAuditTest` stays green unchanged; no payload snapshot changes.

- [ ] **Step 8: Commit**

```bash
git add src/Concerns/InteractsWithScans.php config/qr-code.php docs/scanning.md tests/Feature/ScanCallbacksTest.php
git commit -m "feat: keep scan callbacks live-only and recover from callback failures"
```

---

## Spec coverage

| Spec requirement | Task |
|---|---|
| `ScanAccepted`/`ScanRejected` events; `QrCodeScanned` on accepted live scans with source and `record_key` | 1 |
| Server doesn't dispatch `qr-scan-rejected` | 1 (guard test) |
| `onScanned` with `ScanResult`/`Get`/`Set`; order state → hooks → callback → events | 2 |
| `onRejected`, `clearOnReject` (config `scan.clear_on_reject`), order clear → callback → event | 3 |
| `hasServerScanChecks()` includes callbacks | 2, 3 |
| Callbacks/events never at submit (owner ruling 4) | 4 |
| Exceptions reported, not swallowed (spec 4.5) | 4 |
| `LogQrCodeScan` implications | "Existing behaviour" section, 4 |
| Browser `qr-scan-rejected`, payload `clearOnReject` | PR 4 (out of scope) |
