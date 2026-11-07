<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

/**
 * One application identifier from the GS1 Barcode Syntax Dictionary.
 *
 * Length and character set are checked. Linters (check digits, dates, ISO
 * codes) are not.
 *
 * @internal Not an extension point. Use Gs1::parse() and Gs1::build().
 */
final readonly class AiRule
{
    private const DIGITS = '0123456789';

    /** GS1 AI encodable character set 82. */
    private const CSET82 = '!"%&\'()*+,-./0123456789:;<=>?ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

    /** GS1 AI encodable character set 39: `#`, `-`, `/`, digits and A-Z. */
    private const CSET39 = '#-/0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** GS1 AI encodable character set 64 (URI-safe base64, no padding). */
    private const CSET64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    /**
     * @param  list<array{optional: bool, kind: string, min: int, max: int}>  $components
     */
    public function __construct(
        public string $ai,
        public bool $fixed,
        public string $title,
        public string $format,
        public array $components,
        public int $minLength,
        public int $maxLength,
    ) {}

    /**
     * Why `$value` cannot follow this AI, or null when it can.
     */
    public function rejectReason(string $value): ?string
    {
        $length = strlen($value);

        if ($length < $this->minLength || $length > $this->maxLength) {
            return $this->lengthReason($length);
        }

        $offset = 0;

        foreach ($this->components as $component) {
            $remaining = $length - $offset;

            if ($component['optional'] && $remaining === 0) {
                return null;
            }

            $take = $component['min'] === $component['max'] ? $component['min'] : $remaining;

            if ($take > $component['max'] || $remaining < $take) {
                return $this->formatReason();
            }

            $slice = substr($value, $offset, $take);

            if (! $this->inCharacterSet($component['kind'], $slice)) {
                return "AI {$this->ai} ({$this->title}) contains a character outside ".$this->characterSetName($component['kind']);
            }

            $offset += $take;
        }

        return $offset === $length ? null : $this->formatReason();
    }

    private function lengthReason(int $length): string
    {
        $expected = $this->minLength === $this->maxLength
            ? (string) $this->minLength
            : "{$this->minLength} to {$this->maxLength}";

        return "AI {$this->ai} ({$this->title}) must be {$expected} characters, got {$length}";
    }

    private function formatReason(): string
    {
        return "AI {$this->ai} ({$this->title}) does not match format {$this->format}";
    }

    private function inCharacterSet(string $kind, string $slice): bool
    {
        $set = match ($kind) {
            'N' => self::DIGITS,
            'X' => self::CSET82,
            'Y' => self::CSET39,
            'Z' => self::CSET64,
            default => '',
        };

        return $slice !== '' && $set !== '' && strspn($slice, $set) === strlen($slice);
    }

    private function characterSetName(string $kind): string
    {
        return match ($kind) {
            'N' => 'digits',
            'X' => 'GS1 character set 82',
            'Y' => 'GS1 character set 39',
            'Z' => 'GS1 character set 64',
            default => 'its character set',
        };
    }
}
