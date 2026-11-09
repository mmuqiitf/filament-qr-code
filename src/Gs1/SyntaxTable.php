<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

use RuntimeException;

/**
 * The vendored GS1 Barcode Syntax Dictionary (resources/gs1, Apache-2.0).
 *
 * @internal Not an extension point. Use Gs1::parse() and Gs1::build().
 */
final class SyntaxTable
{
    /** @var array<string, AiRule>|null */
    private static ?array $rules = null;

    public static function find(string $ai): ?AiRule
    {
        return self::rules()[$ai] ?? null;
    }

    /**
     * @return array<string, AiRule>
     */
    public static function rules(): array
    {
        return self::$rules ??= self::load();
    }

    /**
     * @return array<string, AiRule>
     */
    private static function load(): array
    {
        $path = dirname(__DIR__, 2).'/resources/gs1/gs1-syntax-dictionary.txt';
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("GS1 syntax dictionary is missing at [{$path}].");
        }

        $rules = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $lineNumber => $line) {
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            foreach (self::entries($line, $lineNumber + 1) as $ai => $rule) {
                $rules[$ai] = $rule;
            }
        }

        return $rules;
    }

    /**
     * @return array<string, AiRule>
     */
    private static function entries(string $line, int $lineNumber): array
    {
        $title = '';
        $hash = strpos($line, '#');

        if ($hash !== false) {
            $title = trim(substr($line, $hash + 1));
            $line = substr($line, 0, $hash);
        }

        $tokens = preg_split('/\s+/', trim($line)) ?: [];

        if ($tokens === []) {
            throw new RuntimeException("GS1 syntax dictionary line {$lineNumber} is empty.");
        }

        $ais = $tokens[0];
        $index = 1;
        $fixed = false;

        if (isset($tokens[$index]) && self::isFlagToken($tokens[$index])) {
            $fixed = str_contains($tokens[$index], '*');
            $index++;
        }

        $components = [];
        $format = [];

        while (isset($tokens[$index]) && self::isComponent($tokens[$index])) {
            $components[] = self::component($tokens[$index], $lineNumber);
            $format[] = $tokens[$index];
            $index++;
        }

        if ($components === []) {
            throw new RuntimeException("GS1 syntax dictionary line {$lineNumber} has no format for [{$ais}].");
        }

        $minLength = 0;
        $maxLength = 0;
        $optionalStarted = false;

        foreach ($components as $position => $component) {
            if ($component['optional']) {
                $optionalStarted = true;
            } elseif ($optionalStarted) {
                throw new RuntimeException("GS1 syntax dictionary line {$lineNumber} has a mandatory component after an optional one.");
            }

            $variable = $component['min'] !== $component['max'];

            if ($variable && $position !== array_key_last($components)) {
                throw new RuntimeException("GS1 syntax dictionary line {$lineNumber} has a variable component before the end.");
            }

            if (! $component['optional']) {
                $minLength += $component['min'];
            }

            $maxLength += $component['max'];
        }

        if ($fixed && ($minLength !== $maxLength || $optionalStarted)) {
            throw new RuntimeException("GS1 syntax dictionary line {$lineNumber} is marked fixed-length but the format varies.");
        }

        $ruleFor = fn (string $ai): AiRule => new AiRule(
            $ai,
            $fixed,
            $title,
            implode(' ', $format),
            $components,
            $minLength,
            $maxLength,
        );

        if (preg_match('/^(\d+)-(\d+)$/', $ais, $range) === 1) {
            $start = (int) $range[1];
            $end = (int) $range[2];
            $width = strlen($range[1]);
            $rules = [];

            for ($ai = $start; $ai <= $end; $ai++) {
                $key = str_pad((string) $ai, $width, '0', STR_PAD_LEFT);
                $rules[$key] = $ruleFor($key);
            }

            return $rules;
        }

        return [$ais => $ruleFor($ais)];
    }

    private static function isFlagToken(string $token): bool
    {
        return ! self::isComponent($token)
            && ! str_contains($token, '=')
            && preg_match('/^[*!?"$%&\'()+,\-.\/:;<=>@\[\]^_`{|}~]+$/', $token) === 1;
    }

    private static function isComponent(string $token): bool
    {
        return preg_match('/^(\[)?([NXYZ])(\d+|\.\.\d+)\]?(?:,[A-Za-z0-9]+)*$/', $token) === 1;
    }

    /**
     * @return array{optional: bool, kind: string, min: int, max: int, linters: list<string>}
     */
    private static function component(string $token, int $lineNumber): array
    {
        if (preg_match('/^(\[)?([NXYZ])(\d+|\.\.\d+)(\])?/', $token, $match) !== 1) {
            throw new RuntimeException("GS1 syntax dictionary line {$lineNumber} has a bad component [{$token}].");
        }

        $open = $match[1] === '[';
        $close = ($match[4] ?? '') === ']';

        if ($open !== $close) {
            throw new RuntimeException("GS1 syntax dictionary line {$lineNumber} has an unmatched bracket in [{$token}].");
        }

        $length = $match[3];
        $variable = str_starts_with($length, '..');
        $linters = array_values(array_filter(explode(',', substr($token, strlen($match[0]))), fn (string $linter): bool => $linter !== ''));

        return [
            'optional' => $open,
            'kind' => $match[2],
            'min' => $variable ? 1 : (int) $length,
            'max' => $variable ? (int) substr($length, 2) : (int) $length,
            'linters' => $linters,
        ];
    }
}
