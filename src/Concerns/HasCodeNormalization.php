<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Mmuqiitf\FilamentQrCode\Support\CodeNormalizer;

trait HasCodeNormalization
{
    protected ?CodeNormalizer $codeNormalizer = null;

    /**
     * Clean every scanned code in the browser (before duplicate detection and
     * step matching) and again on the server.
     *
     * @param  string|list<string>|null  $stripPrefix
     */
    public function normalizeWith(bool $trim = true, bool $uppercase = false, string|array|null $stripPrefix = null): static
    {
        $this->codeNormalizer = new CodeNormalizer(
            trim: $trim,
            uppercase: $uppercase,
            stripPrefixes: array_values(array_filter((array) $stripPrefix, fn (string $prefix): bool => $prefix !== '')),
        );

        return $this;
    }

    public function getCodeNormalizer(): ?CodeNormalizer
    {
        return $this->codeNormalizer;
    }

    protected function applyCodeNormalizer(string $code): string
    {
        return $this->codeNormalizer?->apply($code) ?? trim($code);
    }

    /** @return array{normalize?: array{trim: bool, uppercase: bool, stripPrefixes: list<string>}} */
    public function getCodeNormalizationScanPayload(): array
    {
        return $this->codeNormalizer === null ? [] : ['normalize' => $this->codeNormalizer->toPayload()];
    }
}
