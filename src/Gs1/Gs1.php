<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;

/**
 * Parse and build GS1 element strings. `Gs1::SEPARATOR` is the group separator (ASCII 29).
 *
 * Accepts the bracketed form `(01)09521101530001(10)LOT-1`, the unbracketed
 * form with group separators, and an optional AIM symbology identifier
 * (`]C1`, `]e0`, `]d2`, `]Q3`). Length and character set come from the GS1
 * Barcode Syntax Dictionary. Check digits, dates and AI pairing rules are
 * not applied.
 */
final class Gs1
{
    /** Group separator, the character scanners return for FNC1 between fields. */
    public const SEPARATOR = "\x1D";

    /**
     * Read an element string into application identifiers.
     *
     * @throws InvalidGs1Exception
     */
    public static function parse(string $input): ElementString
    {
        $original = $input;
        $input = trim($input);

        if (preg_match('/^\][A-Za-z]\d/', $input) === 1) {
            $input = substr($input, 3);
        }

        $input = ltrim($input, self::SEPARATOR);

        if ($input === '') {
            throw new InvalidGs1Exception($original, 'the element string is empty');
        }

        $pairs = str_starts_with($input, '(')
            ? self::parseBracketed($original, $input)
            : self::parseRaw($original, $input);

        return new ElementString(self::elements($original, $pairs), self::encode($pairs));
    }

    /**
     * Build an element string from application identifier => value, in order.
     * Write keys as strings (`'01'`, `'10'`). PHP stores `'10'` as an int key;
     * that is cast back. A leading zero (`'01'`, `'00'`) only survives as a string.
     *
     * @param  array<int|string, mixed>  $elements
     *
     * @throws InvalidGs1Exception
     */
    public static function build(array $elements): ElementString
    {
        if ($elements === []) {
            throw new InvalidGs1Exception('', 'the element string is empty');
        }

        if (array_is_list($elements)) {
            throw new InvalidGs1Exception('', 'pass application identifiers as string keys, for example [\'01\' => $gtin]');
        }

        $pairs = [];

        foreach ($elements as $ai => $value) {
            // PHP casts a numeric string key such as '10' or '8003' to int.
            if (is_int($ai)) {
                $ai = (string) $ai;
            }

            if (! is_string($value)) {
                throw new InvalidGs1Exception('', 'application identifiers and values must be strings');
            }

            $pairs[] = [$ai, $value];
        }

        $shown = self::describe($pairs);

        return new ElementString(self::elements($shown, $pairs), self::encode($pairs));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function parseBracketed(string $original, string $input): array
    {
        $pairs = [];
        $offset = 0;
        $length = strlen($input);

        while ($offset < $length) {
            if ($input[$offset] !== '(') {
                throw new InvalidGs1Exception($original, 'expected ( before application identifier '.self::snippet($input, $offset));
            }

            $close = strpos($input, ')', $offset + 1);

            if ($close === false) {
                throw new InvalidGs1Exception($original, 'application identifier '.self::snippet($input, $offset).' is missing )');
            }

            $ai = substr($input, $offset + 1, $close - $offset - 1);
            $next = strpos($input, '(', $close + 1);
            $end = $next === false ? $length : $next;
            $pairs[] = [$ai, substr($input, $close + 1, $end - $close - 1)];
            $offset = $end;
        }

        return $pairs;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function parseRaw(string $original, string $input): array
    {
        $pairs = [];
        $offset = 0;
        $length = strlen($input);

        while ($offset < $length) {
            if ($input[$offset] === self::SEPARATOR) {
                $offset++;

                continue;
            }

            $ai = self::matchAi($input, $offset);

            if ($ai === null) {
                throw new InvalidGs1Exception($original, 'unknown application identifier '.self::snippet($input, $offset));
            }

            $rule = SyntaxTable::find($ai);
            $offset += strlen($ai);

            if ($rule === null) {
                throw new InvalidGs1Exception($original, "unknown application identifier {$ai}");
            }

            if ($rule->fixed) {
                if ($offset + $rule->maxLength > $length) {
                    $pairs[] = [$ai, substr($input, $offset)];

                    break;
                }

                $pairs[] = [$ai, substr($input, $offset, $rule->maxLength)];
                $offset += $rule->maxLength;

                continue;
            }

            $separator = strpos($input, self::SEPARATOR, $offset);
            $end = $separator === false ? $length : $separator;
            $pairs[] = [$ai, substr($input, $offset, $end - $offset)];
            $offset = $separator === false ? $length : $separator + 1;
        }

        if ($pairs === []) {
            throw new InvalidGs1Exception($original, 'the element string is empty');
        }

        return $pairs;
    }

    private static function matchAi(string $input, int $offset): ?string
    {
        $length = strlen($input);

        foreach ([4, 3, 2] as $size) {
            if ($offset + $size > $length) {
                continue;
            }

            $candidate = substr($input, $offset, $size);

            if (SyntaxTable::find($candidate) !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return list<Element>
     */
    private static function elements(string $shown, array $pairs): array
    {
        $elements = [];
        $seen = [];

        foreach ($pairs as [$ai, $value]) {
            $rule = SyntaxTable::find($ai);

            if ($rule === null) {
                throw new InvalidGs1Exception($shown, "unknown application identifier {$ai}");
            }

            if (isset($seen[$ai])) {
                throw new InvalidGs1Exception($shown, "duplicate application identifier {$ai}");
            }

            $reason = $rule->rejectReason($value);

            if ($reason !== null) {
                throw new InvalidGs1Exception($shown, $reason);
            }

            $seen[$ai] = true;
            $elements[] = new Element($ai, $value, $rule->title);
        }

        return $elements;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     */
    private static function encode(array $pairs): string
    {
        $out = '';
        $last = array_key_last($pairs);

        foreach ($pairs as $index => [$ai, $value]) {
            $rule = SyntaxTable::find($ai);
            $out .= $ai.$value;

            if ($rule !== null && ! $rule->fixed && $index !== $last) {
                $out .= self::SEPARATOR;
            }
        }

        return $out;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     */
    private static function describe(array $pairs): string
    {
        $out = '';

        foreach ($pairs as [$ai, $value]) {
            $out .= '('.$ai.')'.$value;
        }

        return $out;
    }

    private static function snippet(string $input, int $offset): string
    {
        $snippet = substr($input, $offset, 4);

        return $snippet === '' ? 'at the end' : '['.$snippet.']';
    }
}
