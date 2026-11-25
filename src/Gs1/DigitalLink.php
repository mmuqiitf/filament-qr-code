<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;

/**
 * Parse and build uncompressed GS1 Digital Link URIs. Path and query
 * application identifiers become the same ElementString model as
 * Gs1::parse(); check digits and dates are enforced the same way.
 *
 * Qualifier order and which AIs may appear as a primary key or a data
 * attribute come from the vendored GS1 Barcode Syntax Dictionary
 * (`dlpkey` and the `?` flag). URI compression and a resolver are not
 * implemented.
 */
final class DigitalLink
{
    public const DEFAULT_DOMAIN = 'https://id.gs1.org';

    /**
     * Read an uncompressed Digital Link URI (http or https, path and/or query).
     *
     * @throws InvalidGs1Exception
     */
    public static function parse(string $uri): ElementString
    {
        $original = $uri;
        $uri = trim($uri);
        $parts = parse_url($uri);

        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new InvalidGs1Exception($original, 'this is not a Digital Link URI', kind: InvalidGs1Exception::SYNTAX);
        }

        $pairs = self::pairsFromPath($original, $parts['path'] ?? '');
        $pathAis = array_column($pairs, 0);

        foreach (self::queryPairs($original, $parts['query'] ?? '') as [$ai, $value]) {
            if (in_array($ai, $pathAis, true)) {
                throw new InvalidGs1Exception($original, "duplicate application identifier {$ai}", kind: InvalidGs1Exception::DUPLICATE_AI, ai: $ai);
            }

            $pairs[] = [$ai, $value];
            $pathAis[] = $ai;
        }

        if ($pairs === []) {
            throw new InvalidGs1Exception($original, 'a Digital Link URI needs a primary key', kind: InvalidGs1Exception::SYNTAX);
        }

        $primary = self::primaryKeyFromPairs($original, $pairs);
        self::assertQualifierOrder($original, $primary, self::pathQualifiers($primary, $parts['path'] ?? ''));

        try {
            return Gs1::build(self::associative($pairs));
        } catch (InvalidGs1Exception $e) {
            throw new InvalidGs1Exception($original, $e->reason, $e, $e->kind, $e->ai);
        }
    }

    /**
     * Write a Digital Link URI. Qualifiers follow the dictionary order in
     * the path; other data attributes go in the query string. `$domain`
     * overrides `qr-code.gs1.digital_link.domain` (default https://id.gs1.org).
     *
     * @param  ElementString|array<int|string, mixed>  $elements
     *
     * @throws InvalidGs1Exception
     */
    public static function build(ElementString|array $elements, ?string $domain = null): string
    {
        $string = $elements instanceof ElementString ? $elements : Gs1::build($elements);
        $map = $string->toArray();
        $primary = self::uniquePrimaryKey($map);
        $rule = SyntaxTable::find($primary);
        $sequences = $rule->digitalLinkQualifiers ?? [];
        $qualifiers = self::chooseQualifierSequence(array_keys($map), $primary, $sequences);

        $path = '';
        $used = [];

        foreach ([$primary, ...$qualifiers] as $ai) {
            if (! array_key_exists($ai, $map)) {
                continue;
            }

            $path .= '/'.$ai.'/'.rawurlencode($map[$ai]);
            $used[$ai] = true;
        }

        $query = [];

        foreach (self::sortedAis($map) as $ai) {
            if (isset($used[$ai])) {
                continue;
            }

            $attribute = SyntaxTable::find($ai);

            if ($attribute === null || ! $attribute->digitalLinkAttribute) {
                throw new InvalidGs1Exception($string->bracketed(), "application identifier {$ai} is not allowed in a Digital Link URI", kind: InvalidGs1Exception::SYNTAX, ai: $ai);
            }

            $query[] = rawurlencode($ai).'='.rawurlencode($map[$ai]);
        }

        $uri = self::domain($domain).$path;

        if ($query !== []) {
            $uri .= '?'.implode('&', $query);
        }

        return $uri;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function pairsFromPath(string $original, string $path): array
    {
        $segments = array_values(array_filter(explode('/', $path), fn (string $segment): bool => $segment !== ''));
        $offset = 0;

        while (isset($segments[$offset]) && SyntaxTable::find(rawurldecode($segments[$offset])) === null) {
            $offset++;
        }

        if (! isset($segments[$offset])) {
            return [];
        }

        $first = rawurldecode($segments[$offset]);

        if (SyntaxTable::find($first)?->digitalLinkQualifiers === null) {
            throw new InvalidGs1Exception($original, 'a Digital Link URI needs a primary key', kind: InvalidGs1Exception::SYNTAX, ai: $first);
        }

        $remaining = array_slice($segments, $offset);

        if (count($remaining) % 2 !== 0) {
            throw new InvalidGs1Exception($original, 'compressed Digital Link URIs are not supported', kind: InvalidGs1Exception::SYNTAX);
        }

        $pairs = [];

        for ($i = 0; $i < count($remaining); $i += 2) {
            $ai = rawurldecode($remaining[$i]);
            $value = rawurldecode($remaining[$i + 1]);

            if (SyntaxTable::find($ai) === null) {
                throw new InvalidGs1Exception($original, 'compressed Digital Link URIs are not supported', kind: InvalidGs1Exception::SYNTAX);
            }

            $pairs[] = [$ai, self::normaliseValue($ai, $value)];
        }

        return $pairs;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function queryPairs(string $original, string $query): array
    {
        if ($query === '') {
            return [];
        }

        $pairs = [];

        foreach (explode('&', $query) as $part) {
            if ($part === '') {
                continue;
            }

            $eq = strpos($part, '=');
            $key = rawurldecode($eq === false ? $part : substr($part, 0, $eq));
            $value = rawurldecode(str_replace('+', ' ', $eq === false ? '' : substr($part, $eq + 1)));

            if (preg_match('/^\d{2,4}$/', $key) !== 1) {
                continue;
            }

            if (SyntaxTable::find($key) === null) {
                throw new InvalidGs1Exception($original, "unknown application identifier {$key}", kind: InvalidGs1Exception::UNKNOWN_AI, ai: $key);
            }

            $pairs[] = [$key, self::normaliseValue($key, $value)];
        }

        return $pairs;
    }

    private static function normaliseValue(string $ai, string $value): string
    {
        if ($ai === '01' && ctype_digit($value) && in_array(strlen($value), [8, 12, 13], true)) {
            return str_pad($value, 14, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     */
    private static function primaryKeyFromPairs(string $original, array $pairs): string
    {
        $keys = [];

        foreach ($pairs as [$ai]) {
            if (SyntaxTable::find($ai)?->digitalLinkQualifiers !== null) {
                $keys[] = $ai;
            }
        }

        if ($keys === []) {
            throw new InvalidGs1Exception($original, 'a Digital Link URI needs a primary key', kind: InvalidGs1Exception::SYNTAX);
        }

        if (count($keys) > 1) {
            throw new InvalidGs1Exception($original, 'a Digital Link URI can have only one primary key', kind: InvalidGs1Exception::SYNTAX, ai: $keys[1]);
        }

        return $keys[0];
    }

    /**
     * Qualifiers that appeared in the path (not the query), in path order.
     *
     * @return list<string>
     */
    private static function pathQualifiers(string $primary, string $path): array
    {
        $pathAis = array_column(self::pairsFromPath('https://host.invalid'.$path, $path), 0);
        $sequences = SyntaxTable::find($primary)->digitalLinkQualifiers ?? [];
        $allowed = self::qualifierSet($sequences);
        $qualifiers = [];

        foreach ($pathAis as $ai) {
            if ($ai !== $primary && isset($allowed[$ai])) {
                $qualifiers[] = $ai;
            }
        }

        return $qualifiers;
    }

    /**
     * @param  list<string>  $qualifiers
     */
    private static function assertQualifierOrder(string $original, string $primary, array $qualifiers): void
    {
        if ($qualifiers === []) {
            return;
        }

        $sequences = SyntaxTable::find($primary)->digitalLinkQualifiers ?? [];

        foreach ($sequences as $sequence) {
            if (array_diff($qualifiers, $sequence) === []) {
                if (self::isSubsequence($qualifiers, $sequence)) {
                    return;
                }

                throw new InvalidGs1Exception($original, 'qualifiers are not in the order required for this primary key', kind: InvalidGs1Exception::SYNTAX, ai: $primary);
            }
        }

        throw new InvalidGs1Exception($original, 'qualifiers from different sequences cannot be mixed', kind: InvalidGs1Exception::SYNTAX, ai: $primary);
    }

    /**
     * @param  list<string|int>  $ais
     * @param  list<list<string>>  $sequences
     * @return list<string>
     */
    private static function chooseQualifierSequence(array $ais, string $primary, array $sequences): array
    {
        $present = [];

        $allowed = self::qualifierSet($sequences);

        foreach ($ais as $ai) {
            $ai = (string) $ai;
            if ($ai !== $primary && isset($allowed[$ai])) {
                $present[] = $ai;
            }
        }

        if ($present === []) {
            return [];
        }

        foreach ($sequences as $sequence) {
            if (array_diff($present, $sequence) === []) {
                return $sequence;
            }
        }

        throw new InvalidGs1Exception('', 'qualifiers from different sequences cannot be mixed', kind: InvalidGs1Exception::SYNTAX, ai: $primary);
    }

    /**
     * @param  list<list<string>>  $sequences
     * @return array<string, true>
     */
    private static function qualifierSet(array $sequences): array
    {
        $allowed = [];

        foreach ($sequences as $sequence) {
            foreach ($sequence as $ai) {
                $allowed[$ai] = true;
            }
        }

        return $allowed;
    }

    /**
     * @param  list<string>  $needles
     * @param  list<string>  $haystack
     */
    private static function isSubsequence(array $needles, array $haystack): bool
    {
        $position = 0;

        foreach ($needles as $needle) {
            $found = array_search($needle, array_slice($haystack, $position), true);

            if ($found === false) {
                return false;
            }

            $position += (int) $found + 1;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $map
     */
    private static function uniquePrimaryKey(array $map): string
    {
        $keys = [];

        foreach (array_keys($map) as $ai) {
            if (SyntaxTable::find((string) $ai)?->digitalLinkQualifiers !== null) {
                $keys[] = (string) $ai;
            }
        }

        if ($keys === []) {
            throw new InvalidGs1Exception('', 'a Digital Link URI needs a primary key', kind: InvalidGs1Exception::SYNTAX);
        }

        if (count($keys) > 1) {
            throw new InvalidGs1Exception('', 'a Digital Link URI can have only one primary key', kind: InvalidGs1Exception::SYNTAX, ai: $keys[1]);
        }

        return $keys[0];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return array<string, string>
     */
    private static function associative(array $pairs): array
    {
        $map = [];

        foreach ($pairs as [$ai, $value]) {
            $map[$ai] = $value;
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $map
     * @return list<string>
     */
    private static function sortedAis(array $map): array
    {
        $ais = array_map(strval(...), array_keys($map));
        sort($ais, SORT_STRING);

        return $ais;
    }

    private static function domain(?string $domain): string
    {
        if ($domain === null || $domain === '') {
            $configured = config('qr-code.gs1.digital_link.domain');
            $domain = is_string($configured) && $configured !== '' ? $configured : self::DEFAULT_DOMAIN;
        }

        $domain = rtrim($domain, '/');

        if (preg_match('~^https?://[^/?#]+~i', $domain) !== 1) {
            throw new InvalidGs1Exception($domain, 'the Digital Link domain must be an http or https URL', kind: InvalidGs1Exception::SYNTAX);
        }

        return $domain;
    }
}
