<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasGs1Scan;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Support\ScanMessages;
use Throwable;

class QrHardwareScannerListener extends Component
{
    use HasFeedback;
    use HasGs1Scan;
    use HasHardwareScanner;
    use HasScanPayload;

    protected string $view = 'filament-qr-code::components.qr-hardware-scanner-listener';

    /**
     * @var array<int, string>|Closure
     */
    protected array|Closure $fields = [];

    protected bool|Closure $autoFocusNext = true;

    protected bool|Closure|null $fallbackToAnyInput = null;

    /**
     * Create a Station Listener routing gun scans into these fields.
     *
     * @param  array<int, string>|Closure  $fields
     */
    public static function make(array|Closure $fields = []): static
    {
        $static = app(static::class);
        $static->fields($fields);

        return $static;
    }

    /**
     * Field names (dot paths allowed) the listener routes scans into.
     *
     * @param  array<int, string>|Closure  $fields
     */
    public function fields(array|Closure $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Read every gun burst as a GS1 label and fill the mapped fields on the
     * server instead of typing the burst into an input. Same `$fill` and
     * `$require` rules as QrScanner::gs1(); paths are relative to the
     * listener's schema. A rejected label fills nothing and shows its reason
     * under the listener. Give each GS1 listener on one page its own key().
     *
     * @param  array<string, string>|Closure  $fill  AI => field, e.g. `['01' => 'gtin', '17' => 'expires_at']`
     * @param  list<string>|Closure  $require  AIs every scan must carry, e.g. `['01', '17', '10']`
     */
    public function gs1(array|Closure $fill = [], array|Closure $require = []): static
    {
        $this->configureGs1($fill, $require);

        // The browser calls handleGs1Scan() by component key; a stateless component has none by default.
        if ($this->key === null) {
            $this->key('qrGs1Listener');
        }

        return $this;
    }

    /**
     * Check a gun burst as GS1 and fill the mapped fields; called by the
     * listener JS, usable from tests.
     *
     * @return array{passes: bool, code: string, message: ?string}
     */
    #[ExposedLivewireMethod]
    public function handleGs1Scan(string $code): array
    {
        $code = self::sanitizeScannedValue($code);

        if (! $this->isGs1()) {
            return ['passes' => false, 'code' => $code, 'message' => (string) __('filament-qr-code::ui.scan_rejected')];
        }

        $check = $this->getGs1ScanCheck();
        $elements = $check->inspect($code);

        if (! $elements instanceof ElementString) {
            return ['passes' => false, 'code' => $code, 'message' => $elements];
        }

        $this->fillGs1Fields($elements, $check);

        event(new QrCodeScanned(
            code: $elements->bracketed(),
            source: ScanInput::Hardware->value,
            context: ['gs1' => $elements->toArray()],
        ));

        return ['passes' => true, 'code' => $elements->bracketed(), 'message' => null];
    }

    /** Focus the next registered field after each routed scan. */
    public function autoFocusNext(bool|Closure $condition = true): static
    {
        $this->autoFocusNext = $condition;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getFields(): array
    {
        $fields = $this->evaluate($this->fields);
        if (! is_array($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                $result[] = $field;
            }
        }

        return $result;
    }

    /** Fill the first empty form input when no registered field is free; false dispatches `qr-scan-unrouted` (config `hardware_scanner.fallback_to_any_input`). */
    public function fallbackToAnyInput(bool|Closure $condition = true): static
    {
        $this->fallbackToAnyInput = $condition;

        return $this;
    }

    public function shouldFallbackToAnyInput(): bool
    {
        if ($this->fallbackToAnyInput === null) {
            return (bool) config('qr-code.hardware_scanner.fallback_to_any_input', true);
        }

        return (bool) $this->evaluate($this->fallbackToAnyInput);
    }

    public function isAutoFocusNext(): bool
    {
        return (bool) $this->evaluate($this->autoFocusNext);
    }

    /** @return array<string, mixed> */
    public function getComponentScanPayload(): array
    {
        $payload = [
            'fields' => $this->getFields(),
            'fallbackToAnyInput' => $this->shouldFallbackToAnyInput(),
            'autoFocusNext' => $this->isAutoFocusNext(),
            'gs1' => $this->isGs1(),
        ];

        if (! $this->isGs1()) {
            return $payload;
        }

        $payload['scanFailedMessage'] = ScanMessages::scanFailed();

        try {
            $key = $this->getKey();
        } catch (Throwable) {
            return $payload;
        }

        return blank($key) ? $payload : [...$payload, 'componentKey' => $key];
    }
}
