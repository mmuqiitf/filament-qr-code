<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Support\Gs1ScanCheck;

/**
 * Shared `->gs1()` configuration for the scanner field and the Station
 * Listener: which AIs fill which fields and which AIs a scan must carry.
 */
trait HasGs1Scan
{
    protected bool $isGs1 = false;

    /** @var array<int|string, mixed>|Closure */
    protected array|Closure $gs1Fill = [];

    /** @var array<int|string, mixed>|Closure */
    protected array|Closure $gs1Require = [];

    /**
     * @param  array<int|string, mixed>|Closure  $fill
     * @param  array<int|string, mixed>|Closure  $require
     */
    protected function configureGs1(array|Closure $fill, array|Closure $require): void
    {
        // Arrays are checked now, so a typo in an AI fails at boot, not at the first scan.
        if (is_array($fill) && is_array($require)) {
            Gs1ScanCheck::from($fill, $require);
        }

        $this->isGs1 = true;
        $this->gs1Fill = $fill;
        $this->gs1Require = $require;
    }

    /** Whether `->gs1()` is on. */
    public function isGs1(): bool
    {
        return $this->isGs1;
    }

    /** @internal The resolved fill map and require list. */
    public function getGs1ScanCheck(): Gs1ScanCheck
    {
        $fill = $this->evaluate($this->gs1Fill);
        $require = $this->evaluate($this->gs1Require);

        return Gs1ScanCheck::from(is_array($fill) ? $fill : [], is_array($require) ? $require : []);
    }

    /** Write the mapped AIs into their fields, by path relative to this component like `$set`. */
    protected function fillGs1Fields(ElementString $elements, Gs1ScanCheck $check): void
    {
        $set = $this->makeSetUtility();

        foreach ($check->fillValues($elements) as $path => $value) {
            $set($path, $value);
        }
    }
}
