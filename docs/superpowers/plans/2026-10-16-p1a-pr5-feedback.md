# P1a PR 5: Outcome Feedback Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give operators distinct, eyes-off feedback for each server-checked scan outcome: a success tone, vibration and green flash on accept; a two-tone low error sound, error vibration pattern and red flash on reject or failure; silence for duplicates. Fields without checks keep today's single success beep.

**Architecture:** A new `resources/js/feedback.js` maps an outcome (`'success' | 'error'`) plus per-outcome settings to `QrFeedback` calls and a flash class. `QrFeedback` (`audio-feedback.js`) gains `playTones()` and `vibratePattern()`. `qr-scanner.js` calls it from `applyAccepted()` and `applyRejected()` (PR 4), which cover accept, reject and failure. On the PHP side a new `Concerns\HasOutcomeFeedback` trait on `QrScanner` resolves `feedback.success` / `feedback.error` config with per-field overrides (`beep()`, `vibrate(success:, error:)`, `flash()`) into the payload keys `successFeedback` and `errorFeedback`.

**Tech Stack:** Web Audio API, Vibration API, Alpine component objects, Vitest + happy-dom, PHP 8.2 / Filament v5, Pest snapshots, Vite.

**Spec:** `docs/superpowers/specs/2026-10-16-p1a-server-scan-validation-design.md` section 5.3, section 6 (`feedback.success` / `feedback.error`), section 7 (Vitest feedback bullet), row 5 of section 8.

## Global Constraints

- JS: `.agents/skills/spatie-javascript` (4 spaces, single quotes, 120 columns, `const`, strict equality, shorthand methods). Format new JS with Prettier `--tab-width 4 --single-quote --print-width 120`.
- PHP: `.agents/skills/spatie-laravel-php` (strict types, typed returns, early returns, no redundant docblocks).
- Default settings (config, overridable per field):
  - `feedback.success`: `sound => true`, `vibrate => [100]`, `flash => true`
  - `feedback.error`: `sound => true`, `vibrate => [80, 60, 80]`, `flash => true`
- Error tone: two 220 Hz tones of 90 ms, the second starting 140 ms after the first. Success tone: one tone at the field's `beepFrequency` for `beepDurationMs` (unchanged values).
- Flash: class `fi-qr-flash-success` or `fi-qr-flash-danger` on the field wrapper (`this.$el`) for 300 ms. A new flash replaces a running one.
- The existing global switches stay masters: `sound(false)` / `feedback.sound = false` silences every outcome; `vibrate(false)` / `feedback.vibrate = false` disables every pattern. Flash is independent of both.
- **Duplicates are silent**: no sound, no vibration, no flash (ruling R1 below). The `qr-scan-duplicate` event (PR 4) is the app's hook.
- **Fields without checks are unchanged**: `acceptLocally()` keeps `emitScanFeedback()` (one beep at `beepFrequency`/`beepDurationMs`, `vibrateDurationMs` vibration, no flash).
- Only `QrScanner` gets outcome feedback; other surfaces' payloads and snapshots don't change.
- Test-writing rule (from PR 3): record calls into arrays (JS) or with `function () use (&$x)` closures (PHP), never through arrow-function captured references.
- Commit the rebuilt `resources/dist` (CI "Vite build is up to date").
- TDD; verification: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`.

## Rulings made in this plan

- **R1 — duplicates are silent.** At 25 fps the camera re-reads the same code constantly, and a wedge user re-scanning the same item is not an error. An error tone would train operators to ignore it. Apps that want a cue listen to `qr-scan-duplicate`.
- **R2 — API naming.** The owner's brief names `beep(success:, error:)`, `vibrate(...)` and `flash()`. The spec (5.3) sketched `successFeedback()` / `errorFeedback()`. This plan implements the brief: `beep()` and `flash()` are new; `vibrate()` keeps its first parameter (`bool|Closure $condition = true`) and gains `?array $success = null, ?array $error = null`, so existing `->vibrate(false)` calls keep working.
- **R3 — failure uses the error outcome.** A failed or `null` server call is rejected with `scan_failed` (PR 4) and gets the error feedback.

## Files

| File | Responsibility |
|---|---|
| Modify `resources/js/audio-feedback.js` | `playTones(tones)`, `vibratePattern(pattern)` |
| Create `resources/js/feedback.js` | `ERROR_TONES`, `FLASH_MS`, `FLASH_CLASSES`, `playOutcomeFeedback()`, `flashElement()` |
| Create `resources/js/feedback.test.js` | Unit and component feedback tests |
| Modify `resources/js/qr-scanner.js` | Options `successFeedback`, `errorFeedback`; outcome feedback in `applyAccepted()`/`applyRejected()` |
| Modify `resources/css/qr-code.css` | Flash classes |
| Modify `src/Support/ConfigValue.php` | `intList()` |
| Create `src/Concerns/HasOutcomeFeedback.php` | `beep()`, `flash()`, resolution, payload |
| Modify `src/Concerns/HasFeedback.php` | `vibrate()` gains `$success`, `$error` patterns |
| Modify `src/Forms/Components/QrScanner.php`, `src/Support/ScanOptions.php` | Use the trait; register its payload |
| Modify `config/qr-code.php` | `feedback.success`, `feedback.error` |
| Create `tests/Feature/OutcomeFeedbackTest.php` | PHP resolution tests |
| Modify scanner snapshot | Two added keys |
| Modify `docs/scanning.md`, `docs/reference.md`, `CHANGELOG.md`, `resources/dist/*` | Docs, changelog, bundle |

---

### Task 1: Outcome feedback in the browser

**Files:**
- Modify: `resources/js/audio-feedback.js`
- Create: `resources/js/feedback.js`
- Modify: `resources/js/qr-scanner.js`
- Test: `resources/js/feedback.test.js`

**Interfaces:**
- Consumes: PR 4 `applyAccepted(result, field)`, `applyRejected(result, field)`, `handleScanResult()`; `qrFeedback` singleton.
- Produces:
  - `QrFeedback#playTones(tones)` where `tones` is `Array<{ frequency: number, durationMs: number, delayMs?: number }>`; `QrFeedback#vibratePattern(pattern)` where `pattern` is `number[]`.
  - `feedback.js`: `ERROR_TONES`, `FLASH_MS = 300`, `FLASH_CLASSES = { success: 'fi-qr-flash-success', error: 'fi-qr-flash-danger' }`, `playOutcomeFeedback(outcome, settings, { element = null, feedback = qrFeedback, beepFrequency = 880, beepDurationMs = 80 } = {})` with `settings = { sound: boolean, vibrate: number[], flash: boolean } | null`, `flashElement(element, className, durationMs = FLASH_MS)`.
  - `qrScannerComponent()` options `successFeedback = null`, `errorFeedback = null` (Task 2 sends them).

- [ ] **Step 1: Write the failing tests**

First failing test: **"rejected result plays error tone and adds danger flash class"**.

`resources/js/feedback.test.js`:

```js
// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { qrFeedback } from './audio-feedback.js';
import { ERROR_TONES, FLASH_CLASSES, FLASH_MS, flashElement, playOutcomeFeedback } from './feedback.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

const successFeedback = { sound: true, vibrate: [100], flash: true };
const errorFeedback = { sound: true, vibrate: [80, 60, 80], flash: true };

function recordFeedback() {
    const played = [];
    vi.spyOn(qrFeedback, 'playTones').mockImplementation((tones) => played.push(['tones', tones]));
    vi.spyOn(qrFeedback, 'vibratePattern').mockImplementation((pattern) => played.push(['vibrate', pattern]));
    vi.spyOn(qrFeedback, 'trigger').mockImplementation((options) => played.push(['legacy', options]));

    return played;
}

function mountScanner({ respond, ...options } = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"></div>';
    const element = document.querySelector('[data-field-name="sku"]');
    const wire = createWireSpy({ respond });
    const scanner = mountComponent(
        qrScannerComponent({
            statePath: 'data.sku',
            hardwareScanner: false,
            serverCheck: true,
            componentKey: 'form.sku',
            cooldownMs: 1500,
            beepFrequency: 880,
            beepDurationMs: 80,
            successFeedback,
            errorFeedback,
            ...options,
        }),
        { element, wire },
    );

    return { scanner, element };
}

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    document.body.innerHTML = '';
});

describe('scanner outcome feedback', () => {
    it('rejected result plays error tone and adds danger flash class', async () => {
        const played = recordFeedback();
        const { scanner, element } = mountScanner({
            respond: () => Promise.resolve({ passes: false, code: 'X', source: 'camera', message: 'Nope', record_key: null }),
        });

        await scanner.handleScanResult('X');

        expect(played).toEqual([
            ['tones', ERROR_TONES],
            ['vibrate', [80, 60, 80]],
        ]);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(true);

        vi.advanceTimersByTime(FLASH_MS);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(false);
    });

    it('accepted result plays the success tone, success vibration and success flash', async () => {
        const played = recordFeedback();
        const { scanner, element } = mountScanner({
            respond: () => Promise.resolve({ passes: true, code: 'A', source: 'camera', message: null, record_key: null }),
        });

        await scanner.handleScanResult('A');

        expect(played).toEqual([
            ['tones', [{ frequency: 880, durationMs: 80 }]],
            ['vibrate', [100]],
        ]);
        expect(element.classList.contains(FLASH_CLASSES.success)).toBe(true);
    });

    it('a failed server call gets the error feedback', async () => {
        const played = recordFeedback();
        vi.spyOn(console, 'error').mockImplementation(() => {});
        const { scanner, element } = mountScanner({ respond: () => Promise.reject(new Error('offline')) });

        await scanner.handleScanResult('X');

        expect(played[0]).toEqual(['tones', ERROR_TONES]);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(true);
    });

    it('a duplicate is silent and does not flash', async () => {
        const { scanner, element } = mountScanner({
            respond: () => Promise.resolve({ passes: true, code: 'A', source: 'camera', message: null, record_key: null }),
        });
        await scanner.handleScanResult('A');
        vi.advanceTimersByTime(FLASH_MS);
        const played = recordFeedback();

        await scanner.handleScanResult('A');

        expect(played).toEqual([]);
        expect(element.className).toBe('');
    });

    it('keeps the legacy beep and no flash for fields without checks', () => {
        const played = recordFeedback();
        const { scanner, element } = mountScanner({ serverCheck: false, cooldownMs: 0 });

        scanner.handleScanResult('PRD-1');

        expect(played.map(([kind]) => kind)).toEqual(['legacy']);
        expect(element.className).toBe('');
    });
});

describe('playOutcomeFeedback', () => {
    it('honours disabled sound, empty vibration and disabled flash', () => {
        const played = [];
        const feedback = {
            playTones: (tones) => played.push(['tones', tones]),
            vibratePattern: (pattern) => played.push(['vibrate', pattern]),
        };
        const element = document.createElement('div');

        playOutcomeFeedback('error', { sound: false, vibrate: [], flash: false }, { element, feedback });

        expect(played).toEqual([]);
        expect(element.className).toBe('');
    });

    it('does nothing without settings', () => {
        const played = [];
        const feedback = { playTones: () => played.push('tones'), vibratePattern: () => played.push('vibrate') };

        playOutcomeFeedback('success', null, { feedback });

        expect(played).toEqual([]);
    });

    it('replaces a running flash with the new outcome', () => {
        const element = document.createElement('div');

        flashElement(element, FLASH_CLASSES.success);
        flashElement(element, FLASH_CLASSES.error);

        expect([...element.classList]).toEqual([FLASH_CLASSES.error]);
    });
});

describe('QrFeedback', () => {
    it('vibratePattern passes the pattern to navigator.vibrate', () => {
        const patterns = [];
        Object.defineProperty(navigator, 'vibrate', { value: (pattern) => patterns.push(pattern), configurable: true });

        qrFeedback.vibratePattern([80, 60, 80]);

        expect(patterns).toEqual([[80, 60, 80]]);
    });

    it('playTones does not throw without an AudioContext', () => {
        expect(() => qrFeedback.playTones(ERROR_TONES)).not.toThrow();
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npx vitest run resources/js/feedback.test.js`
Expected: FAIL with `Cannot find module './feedback.js'` (and, once it exists, `qrFeedback.playTones` missing).

- [ ] **Step 3: Extend `QrFeedback`** (`resources/js/audio-feedback.js`, add before `trigger`)

```js
    playTones(tones = []) {
        try {
            const context = this.getAudioContext();
            if (!context) return;

            if (context.state === 'suspended') {
                context.resume();
            }

            tones.forEach(({ frequency, durationMs, delayMs = 0 }) => {
                const startAt = context.currentTime + delayMs / 1000;
                const stopAt = startAt + durationMs / 1000;
                const oscillator = context.createOscillator();
                const gain = context.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(frequency, startAt);
                gain.gain.setValueAtTime(0.15, startAt);
                gain.gain.exponentialRampToValueAtTime(0.0001, stopAt);

                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start(startAt);
                oscillator.stop(stopAt);
            });
        } catch (error) {
            console.debug('Audio feedback error:', error);
        }
    }

    vibratePattern(pattern = []) {
        try {
            if (typeof navigator !== 'undefined' && navigator.vibrate && pattern.length > 0) {
                navigator.vibrate(pattern);
            }
        } catch (error) {
            console.debug('Haptic feedback error:', error);
        }
    }
```

- [ ] **Step 4: Create `resources/js/feedback.js`**

```js
import { qrFeedback } from './audio-feedback.js';

export const ERROR_TONES = [
    { frequency: 220, durationMs: 90 },
    { frequency: 220, durationMs: 90, delayMs: 140 },
];

export const FLASH_MS = 300;

export const FLASH_CLASSES = {
    success: 'fi-qr-flash-success',
    error: 'fi-qr-flash-danger',
};

const flashTimers = new WeakMap();

export function flashElement(element, className, durationMs = FLASH_MS) {
    clearTimeout(flashTimers.get(element));
    element.classList.remove(...Object.values(FLASH_CLASSES));
    element.classList.add(className);

    flashTimers.set(
        element,
        setTimeout(() => element.classList.remove(className), durationMs),
    );
}

export function playOutcomeFeedback(
    outcome,
    settings,
    { element = null, feedback = qrFeedback, beepFrequency = 880, beepDurationMs = 80 } = {},
) {
    if (!settings) {
        return;
    }

    const { sound = false, vibrate = [], flash = false } = settings;

    if (sound) {
        feedback.playTones(outcome === 'success' ? [{ frequency: beepFrequency, durationMs: beepDurationMs }] : ERROR_TONES);
    }

    if (vibrate.length > 0) {
        feedback.vibratePattern(vibrate);
    }

    if (flash && element) {
        flashElement(element, outcome === 'success' ? FLASH_CLASSES.success : FLASH_CLASSES.error);
    }
}
```

- [ ] **Step 5: Wire into `qr-scanner.js`**

1. Import: `import { playOutcomeFeedback } from './feedback.js';`
2. Options (after `scanFailedMessage`): `successFeedback = null,` and `errorFeedback = null,`.
3. In `applyAccepted()`, replace the `emitScanFeedback(...)` line with:

```js
            if (successFeedback) {
                playOutcomeFeedback('success', successFeedback, { element: this.$el, beepFrequency, beepDurationMs });
            } else {
                emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });
            }
```

4. In `applyRejected()`, after the clear block and before the event:

```js
            playOutcomeFeedback('error', errorFeedback, { element: this.$el, beepFrequency, beepDurationMs });
```

`acceptLocally()` and the duplicate branch are not touched.

- [ ] **Step 6: Run tests to verify they pass**

Run: `npx vitest run resources/js/feedback.test.js && npm test`
Expected: PASS (all files).

- [ ] **Step 7: Commit**

```bash
git add resources/js/audio-feedback.js resources/js/feedback.js resources/js/feedback.test.js resources/js/qr-scanner.js
git commit -m "feat: play outcome feedback for server-checked scans"
```

---

### Task 2: Config and per-field options

**Files:**
- Modify: `src/Support/ConfigValue.php`, `src/Concerns/HasFeedback.php`, `src/Forms/Components/QrScanner.php`, `src/Support/ScanOptions.php`, `config/qr-code.php`
- Create: `src/Concerns/HasOutcomeFeedback.php`
- Test: `tests/Feature/OutcomeFeedbackTest.php`; scanner snapshot

**Interfaces:**
- Consumes: `HasFeedback::hasSound()`, `hasVibration()`.
- Produces:
  - `ConfigValue::intList(string $key, array $default): array` (list of ints ≥ 0; anything else → `$default`).
  - `HasFeedback::vibrate(bool|Closure $condition = true, ?array $success = null, ?array $error = null): static`; protected `?array $successVibrationPattern`, `?array $errorVibrationPattern`.
  - `HasOutcomeFeedback`: `beep(bool|Closure|null $success = null, bool|Closure|null $error = null): static`, `flash(bool|Closure $condition = true): static`, `getSuccessFeedback(): array{sound: bool, vibrate: list<int>, flash: bool}`, `getErrorFeedback(): array{...}`, `getOutcomeFeedbackScanPayload(): array{successFeedback: array, errorFeedback: array}`.

- [ ] **Step 1: Write the failing tests**

First failing test: **"resolves success and error feedback from config defaults"**.

```php
<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

it('resolves success and error feedback from config defaults', function (): void {
    expect(QrScanner::make('sku')->getScanPayload())
        ->toHaveKey('successFeedback', ['sound' => true, 'vibrate' => [100], 'flash' => true])
        ->toHaveKey('errorFeedback', ['sound' => true, 'vibrate' => [80, 60, 80], 'flash' => true]);
});

it('reads overrides from feedback.success and feedback.error config', function (): void {
    config()->set('qr-code.feedback.success', ['sound' => false, 'vibrate' => [50], 'flash' => false]);
    config()->set('qr-code.feedback.error', ['vibrate' => 'not-a-pattern']);

    $scanner = QrScanner::make('sku');

    expect($scanner->getSuccessFeedback())->toBe(['sound' => false, 'vibrate' => [50], 'flash' => false])
        ->and($scanner->getErrorFeedback())->toBe(['sound' => true, 'vibrate' => [80, 60, 80], 'flash' => true]);
});

it('lets each field override beep, vibration patterns and flash', function (): void {
    $scanner = QrScanner::make('sku')
        ->beep(success: false, error: true)
        ->vibrate(success: [30], error: [200, 100, 200])
        ->flash(false);

    expect($scanner->getSuccessFeedback())->toBe(['sound' => false, 'vibrate' => [30], 'flash' => false])
        ->and($scanner->getErrorFeedback())->toBe(['sound' => true, 'vibrate' => [200, 100, 200], 'flash' => false]);
});

it('keeps sound(false) and vibrate(false) as master switches', function (): void {
    $scanner = QrScanner::make('sku')->sound(false)->vibrate(false);

    expect($scanner->getSuccessFeedback())->toBe(['sound' => false, 'vibrate' => [], 'flash' => true])
        ->and($scanner->getErrorFeedback())->toBe(['sound' => false, 'vibrate' => [], 'flash' => true]);
});

it('only adds outcome feedback to the scanner payload', function (): void {
    expect(\Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence::make(['a'])->getScanPayload())
        ->not->toHaveKey('successFeedback');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/OutcomeFeedbackTest.php`
Expected: FAIL with `Failed asserting that an array has the key 'successFeedback'` (and `beep`/`flash` missing). The last test passes already as a guard.

- [ ] **Step 3: Add `ConfigValue::intList()`**

```php
    /**
     * @param  list<int>  $default
     * @return list<int>
     */
    public static function intList(string $key, array $default): array
    {
        $value = self::read($key, $default);

        if (! is_array($value) || ! array_is_list($value)) {
            return $default;
        }

        foreach ($value as $item) {
            if (! is_int($item) || $item < 0) {
                return $default;
            }
        }

        return $value;
    }
```

- [ ] **Step 4: Extend `HasFeedback::vibrate()`**

```php
    /** @var list<int>|null */
    protected ?array $successVibrationPattern = null;

    /** @var list<int>|null */
    protected ?array $errorVibrationPattern = null;

    /**
     * @param  list<int>|null  $success
     * @param  list<int>|null  $error
     */
    public function vibrate(bool|Closure $condition = true, ?array $success = null, ?array $error = null): static
    {
        $this->hasVibration = $condition;
        $this->successVibrationPattern = $success ?? $this->successVibrationPattern;
        $this->errorVibrationPattern = $error ?? $this->errorVibrationPattern;

        return $this;
    }
```

- [ ] **Step 5: Create `src/Concerns/HasOutcomeFeedback.php`**

```php
<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;

trait HasOutcomeFeedback
{
    protected bool|Closure|null $successBeep = null;

    protected bool|Closure|null $errorBeep = null;

    protected bool|Closure|null $hasFlash = null;

    public function beep(bool|Closure|null $success = null, bool|Closure|null $error = null): static
    {
        $this->successBeep = $success ?? $this->successBeep;
        $this->errorBeep = $error ?? $this->errorBeep;

        return $this;
    }

    public function flash(bool|Closure $condition = true): static
    {
        $this->hasFlash = $condition;

        return $this;
    }

    /** @return array{sound: bool, vibrate: list<int>, flash: bool} */
    public function getSuccessFeedback(): array
    {
        return $this->resolveOutcomeFeedback('success', $this->successBeep, $this->successVibrationPattern, [100]);
    }

    /** @return array{sound: bool, vibrate: list<int>, flash: bool} */
    public function getErrorFeedback(): array
    {
        return $this->resolveOutcomeFeedback('error', $this->errorBeep, $this->errorVibrationPattern, [80, 60, 80]);
    }

    /** @return array{successFeedback: array{sound: bool, vibrate: list<int>, flash: bool}, errorFeedback: array{sound: bool, vibrate: list<int>, flash: bool}} */
    public function getOutcomeFeedbackScanPayload(): array
    {
        return [
            'successFeedback' => $this->getSuccessFeedback(),
            'errorFeedback' => $this->getErrorFeedback(),
        ];
    }

    /**
     * @param  list<int>|null  $pattern
     * @param  list<int>  $defaultPattern
     * @return array{sound: bool, vibrate: list<int>, flash: bool}
     */
    protected function resolveOutcomeFeedback(string $outcome, bool|Closure|null $beep, ?array $pattern, array $defaultPattern): array
    {
        $sound = $beep === null
            ? ConfigValue::bool("qr-code.feedback.{$outcome}.sound", true)
            : (bool) $this->evaluate($beep);

        $flash = $this->hasFlash === null
            ? ConfigValue::bool("qr-code.feedback.{$outcome}.flash", true)
            : (bool) $this->evaluate($this->hasFlash);

        return [
            'sound' => $this->hasSound() && $sound,
            'vibrate' => $this->hasVibration()
                ? ($pattern ?? ConfigValue::intList("qr-code.feedback.{$outcome}.vibrate", $defaultPattern))
                : [],
            'flash' => $flash,
        ];
    }
}
```

The trait relies on `HasFeedback` (`hasSound()`, `hasVibration()`, the pattern properties); `QrScanner` uses both.

- [ ] **Step 6: Register and configure**

- `QrScanner`: import and `use HasOutcomeFeedback;` (alphabetical, after `HasHardwareScanner`).
- `ScanOptions::Contributors`: add `HasOutcomeFeedback::class => 'getOutcomeFeedbackScanPayload',` with its import.
- `config/qr-code.php`, inside `feedback`, after `vibrate_duration_ms`:

```php
        // Outcome feedback for fields with server checks (validateUsing, lookup,
        // onScanned, onRejected). sound / vibrate above stay master switches.
        // Override per field with beep(), vibrate(success:, error:) and flash().
        'success' => [
            'sound' => true,
            'vibrate' => [100], // ms pattern
            'flash' => true,
        ],
        'error' => [
            'sound' => true,
            'vibrate' => [80, 60, 80],
            'flash' => true,
        ],
```

- [ ] **Step 7: Run tests and update the snapshot**

Run: `vendor/bin/pest tests/Feature/OutcomeFeedbackTest.php`
Expected: PASS.
Run: `vendor/bin/pest tests/Feature/ScanPayloadCharacterizationTest.php`
Expected: only the `scanner` row fails, with exactly the `errorFeedback` and `successFeedback` blocks added. Then `--update-snapshots`, re-run, PASS.

- [ ] **Step 8: Commit**

```bash
git add src config tests
git commit -m "feat: configure success and error feedback per field"
```

---

### Task 3: Flash styles, docs, changelog and bundle

**Files:**
- Modify: `resources/css/qr-code.css`, `docs/scanning.md`, `docs/reference.md`, `CHANGELOG.md`, `resources/dist/*`

- [ ] **Step 1: Add the flash classes** (end of `resources/css/qr-code.css`)

```css
/* Scan outcome flash (server-checked QrScanner fields) */
.fi-qr-flash-success {
    animation: fi-qr-flash-success 300ms ease-out;
}

.fi-qr-flash-danger {
    animation: fi-qr-flash-danger 300ms ease-out;
}

@keyframes fi-qr-flash-success {
    from {
        box-shadow: 0 0 0 3px rgb(34 197 94 / 0.7);
    }
    to {
        box-shadow: 0 0 0 3px rgb(34 197 94 / 0);
    }
}

@keyframes fi-qr-flash-danger {
    from {
        box-shadow: 0 0 0 3px rgb(239 68 68 / 0.8);
    }
    to {
        box-shadow: 0 0 0 3px rgb(239 68 68 / 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .fi-qr-flash-success,
    .fi-qr-flash-danger {
        animation: none;
    }

    .fi-qr-flash-success {
        outline: 2px solid rgb(34 197 94);
    }

    .fi-qr-flash-danger {
        outline: 2px solid rgb(239 68 68);
    }
}
```

The field wrapper already has `relative`; the box-shadow needs no layout change. Add `rounded-lg` to the wrapper `div` in `resources/views/components/qr-scanner.blade.php` (`class="relative rounded-lg"`) so the ring follows the input's corners.

- [ ] **Step 2: Docs**

Append to "Server-side checks" in `docs/scanning.md`:

````markdown
### Feedback

Fields with server checks give a different cue for each outcome:

| Outcome | Sound | Vibration | Flash |
|---|---|---|---|
| Accepted | one tone (`beepFrequency`, `beepDuration`) | `[100]` | green |
| Rejected or check failed | two low tones | `[80, 60, 80]` | red |
| Duplicate (within cooldown) | none | none | none |

```php
QrScanner::make('sku')
    ->lookup(Product::class, 'sku')
    ->beep(success: true, error: true)
    ->vibrate(success: [50], error: [200, 100, 200])
    ->flash(); // or flash(false)
```

Defaults live in `qr-code.feedback.success` and `qr-code.feedback.error`. `sound(false)` and `vibrate(false)` still turn everything off. Fields without checks keep the single success beep.
````

In `docs/reference.md`, add `beep()`, `vibrate(success:, error:)`, `flash()` to the `QrScanner` method list next to `sound()`/`vibrate()` (find them with `rg -n "vibrate\(" docs/reference.md`).

- [ ] **Step 3: CHANGELOG `## [Unreleased]`**

```markdown
### Added
- Server-side checks for live `QrScanner` scans (P1a). Each scan makes one
  Livewire call to a new `handleScan()` hook (Filament's
  `callSchemaComponentMethod`) and gets a pass/fail result back.
  - `validateUsing()` accepts Laravel rule arrays, rule objects and closures;
    `lookup(Model::class, column, modifyQueryUsing:)` finds a record. Checks
    run in order (rules, lookup, closures) and stop at the first failure, with
    translated messages (`record_not_found`, `scan_failed`). The same checks
    re-run on form submit (`ScanChecksRule`).
  - `onScanned()` / `onRejected()` callbacks with `ScanResult`, `Get` and `Set`
    (live scans only); `clearOnReject()`; events `ScanAccepted` and
    `ScanRejected`. Accepted live scans now fire `QrCodeScanned` (audited when
    `qr-code.audit.enabled`).
  - Browser pipeline: duplicate suppression within `cooldown()` (event
    `qr-scan-duplicate`), the camera pauses while a check runs, the field
    clears on reject and `qr-scan-rejected` / `qr-scan-accepted` fire once.
    Page-level hardware bursts are checked too.
  - Outcome feedback: success and error tones, vibration patterns and a
    green/red flash, configurable in `qr-code.feedback.success` / `.error` and
    per field with `beep()`, `vibrate(success:, error:)` and `flash()`.
- Config: `qr-code.scan.cooldown_ms`, `qr-code.scan.clear_on_reject`,
  `qr-code.feedback.success`, `qr-code.feedback.error`.

### Changed
- `QrScanner` fields without any server check behave exactly as before
  (no extra request, no cooldown, single success beep).
```

- [ ] **Step 4: Rebuild and verify**

Run fresh: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`. Expected: all exit 0; `resources/dist/filament-qr-code.js` and `resources/dist/filament-qr-code.css` change; a second build leaves the tree clean.

- [ ] **Step 5: Commit**

```bash
git add resources/css/qr-code.css resources/views/components/qr-scanner.blade.php docs CHANGELOG.md resources/dist
git commit -m "feat: add outcome flash styles; document P1a; rebuild assets"
```

---

## Spec coverage

| Requirement | Task |
|---|---|
| Error tone, success tone, vibration patterns | 1 |
| Flash classes success / danger | 1 (behaviour), 3 (styles) |
| Wired into accept, reject, failure; duplicate silent (R1) | 1 |
| `beep(success:, error:)`, `vibrate(...)`, `flash()` (R2) | 2 |
| `feedback.success` / `feedback.error`, per-field overrides | 2 |
| Fields without checks unchanged | 1 (guard test), 2 (payload only used by server path) |
| Docs and CHANGELOG | 3 |
