<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;

trait HasSequentialScan
{
    protected string|Closure|null $nextField = null;

    public function nextField(string|Closure|null $fieldName): static
    {
        $this->nextField = $fieldName;

        return $this;
    }

    public function getNextField(): ?string
    {
        return $this->evaluate($this->nextField);
    }

    /** @return array<string, mixed> */
    public function getSequentialScanPayload(): array
    {
        return [
            'nextField' => $this->getNextField(),
        ];
    }
}
