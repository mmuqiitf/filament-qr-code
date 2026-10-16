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
