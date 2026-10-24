<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

/**
 * Built-in clean-up presets, applied in the browser (dedupe, step matching)
 * and again on the server: trim, strip the first matching prefix, uppercase.
 *
 * @internal Not covered by semver; may change in any release.
 */
final readonly class CodeNormalizer
{
    /** @param  list<string>  $stripPrefixes */
    public function __construct(
        public bool $trim = true,
        public bool $uppercase = false,
        public array $stripPrefixes = [],
    ) {}

    public function apply(string $code): string
    {
        if ($this->trim) {
            $code = trim($code);
        }

        foreach ($this->stripPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($code, $prefix)) {
                $code = substr($code, strlen($prefix));

                break;
            }
        }

        return $this->uppercase ? mb_strtoupper($code) : $code;
    }

    /** @return array{trim: bool, uppercase: bool, stripPrefixes: list<string>} */
    public function toPayload(): array
    {
        return ['trim' => $this->trim, 'uppercase' => $this->uppercase, 'stripPrefixes' => $this->stripPrefixes];
    }
}
