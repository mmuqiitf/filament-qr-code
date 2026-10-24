# Extension points

Every seam the package offers, in one place. Each row links to the page that shows it in use.

## PHP

| Seam | Signature | Where |
| --- | --- | --- |
| Record lookup | `->lookup(Model::class, 'column')`, `->lookupUsing(fn (string $code, ...) => mixed, fallback: false)` on `QrScanner` and `ScanStep` | [customizing.md](customizing.md#find-records-yourself-lookupusing) |
| Code normalization | `->normalizeWith(trim: true, uppercase: false, stripPrefix: null)`, `->normalizeUsing(fn ($rawValue) => string)`, `QrScanSequence::normalizeStepUsing()` | [customizing.md](customizing.md#clean-codes-normalizewith-and-normalizeusing) |
| Check stages | `->pipe(ScanCheckStage\|class-string ...$stages)`; `ScanCheckStage::check(ScanCheckContext $context): bool\|string\|null` | [customizing.md](customizing.md#custom-check-stages-pipe) |
| Reject rules | `QrScanner::rejectWhen(fn ($state) => bool, string\|Closure $message)` | [reference.md](reference.md#normalize-live-scans) |
| Whole-set check | `QrScanSequence::validateAllUsing(array\|Closure $rules)`, `->onInvalidSequence(InvalidSequenceBehavior)` | [recipes/manufacturing.md](recipes/manufacturing.md#the-whole-bom) |
| Step closures | `$get`, `$values`, `$step` injected into every step closure | [customizing.md](customizing.md#step-closures-get-get-values-and-step) |
| Step hooks | `->onStepAccepted(fn ($step, $result, $code, $record, $source, $values) => ...)`, `->onStepRejected(...)`, `->onCompleted(fn (array $values) => ...)` | [customizing.md](customizing.md#step-hooks-onstepaccepted--onsteprejected) |
| Conditional steps | `ScanStep::when(fn ($values) => bool)` | [scanning.md](scanning.md#step-order-optional-skips-and-when) |
| Server control | `goToStep()`, `resetSequence()`, `refreshSequence()`, `setRepeatCount()` | [scanning.md](scanning.md#driving-a-sequence-from-page-code) |
| Laravel events | `QrCodeScanned`, `ScanAccepted`, `ScanRejected`, `ScanSequenceCompleted` | [events.md](events.md#laravel-events) |
| Macros | `QrScanner::macro()`, `QrScanSequence::macro()`, `QrCollector::macro()`, `ScanStep::macro()` | [customizing.md](customizing.md#macros) |
| Global defaults | Filament's `QrScanner::configureUsing(fn (QrScanner $field) => ...)` (any component), plus `config/qr-code.php` | [reference.md](reference.md#configuration) |
| Reusable fields | `php artisan make:qr-scan-field Name [--sequence]`, publishable stubs | [customizing.md](customizing.md#reusable-field-classes-make-qr-scan-field) |
| Payload builders | `QrPayload::wifi()`, `vcard()`, `mailto()`, `sms()`, `geo()` | [generating.md](generating.md#common-payloads-without-hand-escaping) |

## Markup and styling

| Seam | Signature | Where |
| --- | --- | --- |
| Attributes | `->extraAttributes([...])` on every scan surface | [customizing.md](customizing.md#styling) |
| Classes | `fi-qr-*` class and `data-qr-part` marker on every part | [recipes/styling.md](recipes/styling.md#classes-and-css-variables) |
| CSS variables | `--fi-qr-viewfinder-bg`, `--fi-qr-viewfinder-radius`, `--fi-qr-reticle-color`, `--fi-qr-reticle-size`, `--fi-qr-laser-color`, `--fi-qr-flash-success`, `--fi-qr-flash-danger` | [recipes/styling.md](recipes/styling.md#classes-and-css-variables) |
| Render hooks | `FilamentView::registerRenderHook(QrCodeRenderHook::..., fn () => ..., scopes: ...)` | [customizing.md](customizing.md#render-hooks) |
| Views | publish `filament-qr-code-views` and keep only the part files you change | [customizing.md](customizing.md#overriding-parts-of-a-view) |

## Browser

| Seam | Signature | Where |
| --- | --- | --- |
| Per-element API | `el.qrScanner` on a `[data-qr-scanner]` root, `el.qrScanSequence` on a `[data-qr-sequence]` root: `feed()`, `pause()`, `resume()`, `setOptions()`, `getState()`, … | [scanning.md](scanning.md#per-element-api) |
| Window events | `qr-scanner-*`, `qr-sequence-*` control events; every `qr-*` event the package fires | [events.md](events.md) |
| Camera decoder | `FilamentQrCode.qrCameraCore.setDecoder(({ formats }) => module)`, `setDecoder(null)` | [scanning.md](scanning.md#camera-decoder-torch-and-zoom), [recipes/zxing-decoder.md](recipes/zxing-decoder.md) |
| Wedge router | `FilamentQrCode.router.register({ id, element, kind, scope, detection, accept, deliver, hold, ownsFocus })` returns `unregister()` | [scanning.md](scanning.md#your-own-inputs-in-the-hardware-router), [recipes/custom-input.md](recipes/custom-input.md) |
| Burst detection | `FilamentQrCode.createHardwareScannerHandler({ burstThresholdMs, minFastKeys, terminators, ... })`, `sanitizeScannedValue()` | [reference.md](reference.md#hardware_scanner) |
| Feedback | `FilamentQrCode.qrFeedback` (beep, vibrate, flash) | [scanning.md](scanning.md#feedback) |
