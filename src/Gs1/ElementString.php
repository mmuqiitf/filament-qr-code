<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

/**
 * Application identifiers from one GS1 element string, in the order they
 * were parsed or built. `raw()` is the canonical unbracketed form.
 */
final readonly class ElementString
{
    /**
     * @param  list<Element>  $elements
     *
     * @internal Constructed by Gs1::parse() and Gs1::build().
     */
    public function __construct(
        private array $elements,
        private string $raw,
    ) {}

    /**
     * Application identifiers in element order.
     *
     * @return list<Element>
     */
    public function elements(): array
    {
        return $this->elements;
    }

    /**
     * The value for an application identifier, or null when it is absent.
     */
    public function get(string $ai): ?string
    {
        foreach ($this->elements as $element) {
            if ($element->ai === $ai) {
                return $element->value;
            }
        }

        return null;
    }

    /**
     * Application identifier to value, in element order.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $values = [];

        foreach ($this->elements as $element) {
            $values[$element->ai] = $element->value;
        }

        return $values;
    }

    /**
     * Unbracketed element string. A group separator (ASCII 29) follows a
     * variable-length AI that is not last. Fixed-length AIs are concatenated.
     */
    public function raw(): string
    {
        return $this->raw;
    }

    /**
     * Bracketed element string, such as `(01)09521101530001(10)LOT-1`.
     */
    public function bracketed(): string
    {
        $out = '';

        foreach ($this->elements as $element) {
            $out .= '('.$element->ai.')'.$element->value;
        }

        return $out;
    }
}
