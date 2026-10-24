# Events

Every event the package fires or listens for. Browser events are dispatched on `window` unless noted. Listen with Alpine (`x-on:qr-scanned.window="..."`), `window.addEventListener(...)`, or Livewire's `#[On('qr-scanned')]` (Livewire forwards browser events dispatched on `window`).

## Browser events the package fires

### Scanner (`QrScanner`)

| Event | `detail` | Fired when |
| --- | --- | --- |
| `qr-scanned` | `{ value, field, nextField, source, recordKey?, mode }` | any accepted scan; `recordKey` is set when a server check found a record |
| `qr-scan-accepted` | `{ value, field, source, recordKey, mode }` | a server-checked scan passed |
| `qr-scan-rejected` | `{ value, field, source, message, mode }` | a server-checked scan failed; also dispatched from PHP with `{ message }` when a `rejectWhen()` predicate matches |
| `qr-scan-duplicate` | `{ value, field, source, mode }` | the same code again within the field's cooldown |
| `qr-scan-dropped` | `{ value, field, source }` | a busy continuous field already held one queued scan |
| `qr-scan-paused` | `{ field }` | a continuous field with `pauseOnReject()` paused after a rejection, or the field was paused |
| `qr-scan-resumed` | `{ field }` | the operator or page code resumed a paused field |
| `qr-hardware-scanned` | `{ value, field }` | a keyboard-wedge burst was delivered to a field |

`source` is `camera`, `hardware`, `upload` or `manual`. `mode` is `single` or `continuous` (see [Continuous mode](scanning.md#continuous-mode)).

### Sequence (`QrScanSequence`)

| Event | `detail` | Fired when |
| --- | --- | --- |
| `qr-sequence-step` | `{ field, value, index, source, skipped? }` | a step accepted a code; `skipped` lists optional steps the code jumped over |
| `qr-sequence-completed` | `{ results, submitted? }` | the last step landed and the whole-set check passed; `submitted` is the `autoSubmit()` outcome |
| `qr-sequence-edited` | `{ field, value }` | the operator typed or corrected a step value |
| `qr-sequence-rejected` | `{ field, value, message, source }` | a step check rejected a code |
| `qr-sequence-invalid` | `{ field, message, values }` | `validateAllUsing()` failed, on completion or on an early finish of a `repeat(n)` step |
| `qr-scan-dropped` | `{ value, field, source }` | a burst arrived while the sequence was busy and one was already queued |

### Collector (`QrCollector`, `QrCollectAction`)

| Event | `detail` | Fired when |
| --- | --- | --- |
| `qr-collector-item-added` | `{ code }` | each accepted batch scan |

### Hardware router (`QrHardwareScannerListener` and every wedge target)

| Event | `detail` | Fired when |
| --- | --- | --- |
| `qr-scan-routed` | `{ value, target, kind, reason, burstId }` | the router delivered a burst; `reason` is `modal`, `focus`, `first-listening`, `fallback` or `queued` |
| `qr-scan-unrouted` | `{ value }` | no target took the burst (see `fallback_to_any_input`) |
| `qr-hardware-scanned` | `{ value, field }` | the listener wrote a burst into a form input |
| `qr-scan-deliver` | `{ value }` | dispatched on the target input itself (bubbles, cancelable); a scanner field cancels it to take the code. Internal, but you can listen on your own inputs |

## Browser events you send

Send these on `window`, or with `$this->dispatch(...)` from PHP. `field` picks the target: a scanner matches its state path (`data.sku`), field name (`sku`) or component key (`form.sku`); a sequence matches its key or state path. Leave `field` out to reach every component of that kind. Details and examples: [Runtime control](scanning.md#window-events).

| Event | `detail` |
| --- | --- |
| `qr-scanner-feed` | `{ field, code, source? }` |
| `qr-scanner-pause` / `qr-scanner-resume` / `qr-scanner-reset` / `qr-scanner-refresh` | `{ field }` |
| `qr-scanner-set-options` | `{ field, options }` |
| `qr-sequence-feed` | `{ field, code, source? }` |
| `qr-sequence-go-to` | `{ field, step }` |
| `qr-sequence-reset` | `{ field, values? }` |
| `qr-sequence-pause` / `qr-sequence-resume` / `qr-sequence-refresh` | `{ field }` |
| `qr-sequence-set-options` | `{ field, options }` |
| `qr-sequence-set-repeat` | `{ field, step, count }` (display only; use `setRepeatCount()` in PHP to change the server check too) |
| `qr-sequence-editable` | `{ enabled }`: switch every sequence between editable and read-only step inputs |

The PHP helpers `goToStep()`, `resetSequence()` and `refreshSequence()` dispatch `qr-sequence-go-to`, `qr-sequence-reset` and `qr-sequence-refresh` for you.

## Laravel events

All live in `Mmuqiitf\FilamentQrCode\Events`. Listen with `Event::listen()` or a listener class.

| Event | Properties | Fired when |
| --- | --- | --- |
| `QrCodeScanned` | `string $code`, `string $source`, `?string $field`, `array $context` | every scan the server sees: `handleScan()`, `triggerOnScan()`, an accepted server check, an accepted sequence step, a `QrCollectAction` scan |
| `ScanAccepted` | `ScanResult $result`, `string $field`, `?string $statePath`, `?string $livewireClass`, `?string $livewireId`, `?string $sequence`, `array $values` | a server check passed (a field, or a sequence step: then `$field` is the step key, `$sequence` the sequence's state path and `$values` the values at the time) |
| `ScanRejected` | same as `ScanAccepted` | a server check failed |
| `ScanSequenceCompleted` | `array $values`, `?string $statePath`, `?string $livewireClass`, `?string $livewireId` | a sequence completed on the server and the whole-set check passed |

With `audit.enabled`, the package's own `Listeners\LogQrCodeScanListener` logs every `QrCodeScanned` (see [Audit scans](reference.md#audit-scans)).
