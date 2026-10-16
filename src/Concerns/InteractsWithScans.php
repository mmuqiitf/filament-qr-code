<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Throwable;

trait InteractsWithScans
{
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

        return $this->checkScanRules($code, $source) ?? ScanResult::accept($code, $source);
    }

    protected function checkScanRules(string $code, ?ScanInput $source): ?ScanResult
    {
        if ($this->scanRules === []) {
            return null;
        }

        $validator = Validator::make(
            ['code' => $code],
            ['code' => $this->scanRules],
            attributes: ['code' => (string) $this->getLabel()],
        );

        if ($validator->passes()) {
            return null;
        }

        return ScanResult::reject($code, $source, (string) $validator->errors()->first('code'));
    }

    public function hasServerScanChecks(): bool
    {
        return $this->scanRules !== []
            || $this->scanCheckClosures !== [];
    }

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
}
