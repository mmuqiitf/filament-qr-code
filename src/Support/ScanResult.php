<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Mmuqiitf\FilamentQrCode\Enums\ScanInput;

final readonly class ScanResult
{
    public function __construct(
        public bool $passes,
        public string $code,
        public ?ScanInput $source,
        public mixed $record = null,
        public ?string $message = null,
    ) {}

    public static function accept(string $code, ?ScanInput $source, mixed $record = null): self
    {
        return new self(passes: true, code: $code, source: $source, record: $record);
    }

    public static function reject(string $code, ?ScanInput $source, string $message): self
    {
        return new self(passes: false, code: $code, source: $source, message: $message);
    }

    /**
     * @return array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed}
     *
     * @internal Payload for the package JS; not covered by semver.
     */
    public function toClient(): array
    {
        return [
            'passes' => $this->passes,
            'code' => $this->code,
            'source' => $this->source?->value,
            'message' => $this->message,
            'record_key' => $this->recordKey(),
        ];
    }

    /** The model key, else `id` or `key` from an array or object record. */
    public function recordKey(): mixed
    {
        $record = $this->record;

        if (is_object($record) && method_exists($record, 'getKey')) {
            return $record->getKey();
        }

        if (is_array($record)) {
            return $record['id'] ?? $record['key'] ?? null;
        }

        if (is_object($record)) {
            return $record->id ?? $record->key ?? null;
        }

        return null;
    }
}
