<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

use Carbon\CarbonImmutable;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;

/**
 * One application identifier from the GS1 Barcode Syntax Dictionary.
 *
 * Length, character set, check digits (`csum`) and calendar dates (`yymmd0`,
 * `yymmdd`, `yyyymmdd`) are checked. Other linters (ISO codes, times, AI
 * pairing) are not.
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
     * @param  list<array{optional: bool, kind: string, min: int, max: int, linters: list<string>}>  $components
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
        return $this->rejection($value)['reason'] ?? null;
    }

    /**
     * Why `$value` cannot follow this AI, with the InvalidGs1Exception kind,
     * or null when it can.
     *
     * @return array{kind: string, reason: string}|null
     */
    public function rejection(string $value): ?array
    {
        $length = strlen($value);

        if ($length < $this->minLength || $length > $this->maxLength) {
            return ['kind' => InvalidGs1Exception::LENGTH, 'reason' => $this->lengthReason($length)];
        }

        $offset = 0;

        foreach ($this->components as $component) {
            $remaining = $length - $offset;

            if ($component['optional'] && $remaining === 0) {
                return null;
            }

            $take = $component['min'] === $component['max'] ? $component['min'] : $remaining;

            if ($take > $component['max'] || $remaining < $take) {
                return ['kind' => InvalidGs1Exception::FORMAT, 'reason' => $this->formatReason()];
            }

            $slice = substr($value, $offset, $take);

            if (! $this->inCharacterSet($component['kind'], $slice)) {
                return [
                    'kind' => InvalidGs1Exception::CHARACTER_SET,
                    'reason' => "AI {$this->ai} ({$this->title}) contains a character outside ".$this->characterSetName($component['kind']),
                ];
            }

            $lint = $this->lintRejection($component['linters'], $slice);

            if ($lint !== null) {
                return $lint;
            }

            $offset += $take;
        }

        return $offset === $length ? null : ['kind' => InvalidGs1Exception::FORMAT, 'reason' => $this->formatReason()];
    }

    /**
     * The first calendar date in `$value`, or null when this AI carries none.
     * Time components are ignored. `$value` must already be valid.
     */
    public function date(string $value): ?CarbonImmutable
    {
        foreach ($this->slices($value) as [$component, $slice]) {
            foreach ($component['linters'] as $linter) {
                if (in_array($linter, Gs1Date::LINTERS, true)) {
                    return Gs1Date::resolve($linter, $slice);
                }
            }
        }

        return null;
    }

    /**
     * The value of an implied-decimal AI (310n to 369n, 390n to 395n) as an
     * exact decimal string, or null for any other AI. The last AI digit is
     * the number of decimal places. For 391n and 393n the leading ISO 4217
     * currency code is not part of the amount.
     */
    public function decimal(string $value): ?string
    {
        if (preg_match('/^(?:3[1-6]\d|39[0-5])(\d)$/', $this->ai, $match) !== 1) {
            return null;
        }

        $slices = $this->slices($value);
        $last = end($slices);

        if ($last === false || ctype_digit($last[1]) !== true) {
            return null;
        }

        $places = (int) $match[1];
        $digits = str_pad($last[1], $places + 1, '0', STR_PAD_LEFT);
        $whole = ltrim(substr($digits, 0, strlen($digits) - $places), '0');
        $whole = $whole === '' ? '0' : $whole;

        if ($places === 0) {
            return $whole;
        }

        return $whole.'.'.substr($digits, -$places);
    }

    /**
     * @param  list<string>  $linters
     * @return array{kind: string, reason: string}|null
     */
    private function lintRejection(array $linters, string $slice): ?array
    {
        foreach ($linters as $linter) {
            if ($linter === 'csum' && ! CheckDigit::validate($slice)) {
                $expected = CheckDigit::calculate(substr($slice, 0, -1));

                return [
                    'kind' => InvalidGs1Exception::CHECK_DIGIT,
                    'reason' => "AI {$this->ai} ({$this->title}) has an invalid check digit, expected {$expected}",
                ];
            }

            if (in_array($linter, Gs1Date::LINTERS, true) && Gs1Date::resolve($linter, $slice) === null) {
                return ['kind' => InvalidGs1Exception::DATE, 'reason' => "AI {$this->ai} ({$this->title}) is not a valid date"];
            }
        }

        return null;
    }

    /**
     * Each present component of `$value` with its slice.
     *
     * @return list<array{0: array{optional: bool, kind: string, min: int, max: int, linters: list<string>}, 1: string}>
     */
    private function slices(string $value): array
    {
        $slices = [];
        $offset = 0;
        $length = strlen($value);

        foreach ($this->components as $component) {
            $remaining = $length - $offset;

            if ($remaining <= 0) {
                break;
            }

            $take = $component['min'] === $component['max'] ? min($component['min'], $remaining) : $remaining;
            $slices[] = [$component, substr($value, $offset, $take)];
            $offset += $take;
        }

        return $slices;
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
