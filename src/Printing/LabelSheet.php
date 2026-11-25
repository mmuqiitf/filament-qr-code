<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Printing;

/**
 * A printable label grid: Avery presets or a custom row/column layout.
 */
final class LabelSheet
{
    /** Custom sheet dimensions; prefer a preset or `make()` in app code. */
    public function __construct(
        public int $columns,
        public ?int $rows,
        public string $labelWidth,
        public string $labelHeight,
        public ?string $pageSize = null,
        public string $pageWidth = '',
        public string $pageHeight = '',
        public string $marginTop = '0',
        public string $marginRight = '0',
        public string $marginBottom = '0',
        public string $marginLeft = '0',
        public string $columnGap = '0',
        public string $rowGap = '0',
    ) {}

    /**
     * Avery 5160 / 8160: 30 labels on US Letter, 2.625 in × 1 in.
     */
    public static function avery5160(): self
    {
        return new self(
            columns: 3,
            rows: 10,
            labelWidth: '66.675mm',
            labelHeight: '25.4mm',
            pageSize: 'letter',
            pageWidth: '215.9mm',
            pageHeight: '279.4mm',
            marginTop: '12.7mm',
            marginRight: '4.7625mm',
            marginBottom: '12.7mm',
            marginLeft: '4.7625mm',
            columnGap: '3.175mm',
            rowGap: '0',
        );
    }

    /**
     * Avery 5163 / 8163: 10 labels on US Letter, 4 in × 2 in.
     */
    public static function avery5163(): self
    {
        return new self(
            columns: 2,
            rows: 5,
            labelWidth: '101.6mm',
            labelHeight: '50.8mm',
            pageSize: 'letter',
            pageWidth: '215.9mm',
            pageHeight: '279.4mm',
            marginTop: '12.7mm',
            marginRight: '3.962mm',
            marginBottom: '12.7mm',
            marginLeft: '3.962mm',
            columnGap: '4.763mm',
            rowGap: '0',
        );
    }

    /**
     * Avery L7160: 21 labels on A4, 63.5 mm × 38.1 mm.
     */
    public static function l7160(): self
    {
        return new self(
            columns: 3,
            rows: 7,
            labelWidth: '63.5mm',
            labelHeight: '38.1mm',
            pageSize: 'A4',
            pageWidth: '210mm',
            pageHeight: '297mm',
            marginTop: '15.15mm',
            marginRight: '7.25mm',
            marginBottom: '15.15mm',
            marginLeft: '7.25mm',
            columnGap: '2.5mm',
            rowGap: '0',
        );
    }

    /**
     * A flowing three-column sheet that matches the existing
     * `.filament-qr-label-sheet` print grid (no fixed page size).
     */
    public static function threeAcross(): self
    {
        return new self(
            columns: 3,
            rows: null,
            labelWidth: '1fr',
            labelHeight: 'auto',
            pageSize: null,
            columnGap: '8mm',
            rowGap: '12mm',
        );
    }

    /** A custom sheet; pass CSS lengths for size, margins and gaps. */
    public static function make(
        int $columns,
        ?int $rows,
        string $labelWidth,
        string $labelHeight,
        ?string $pageSize = null,
        string $pageWidth = '',
        string $pageHeight = '',
        string $marginTop = '0',
        string $marginRight = '0',
        string $marginBottom = '0',
        string $marginLeft = '0',
        string $columnGap = '0',
        string $rowGap = '0',
    ): self {
        if ($pageSize === 'letter' && $pageWidth === '' && $pageHeight === '') {
            $pageWidth = '215.9mm';
            $pageHeight = '279.4mm';
        }

        if ($pageSize === 'A4' && $pageWidth === '' && $pageHeight === '') {
            $pageWidth = '210mm';
            $pageHeight = '297mm';
        }

        return new self(
            columns: $columns,
            rows: $rows,
            labelWidth: $labelWidth,
            labelHeight: $labelHeight,
            pageSize: $pageSize,
            pageWidth: $pageWidth,
            pageHeight: $pageHeight,
            marginTop: $marginTop,
            marginRight: $marginRight,
            marginBottom: $marginBottom,
            marginLeft: $marginLeft,
            columnGap: $columnGap,
            rowGap: $rowGap,
        );
    }

    /** Labels that fit on one page, or null for a flowing sheet. */
    public function labelsPerPage(): ?int
    {
        if ($this->rows === null) {
            return null;
        }

        return $this->columns * $this->rows;
    }

    /**
     * @return array<string, string>
     */
    /** CSS custom properties applied to the sheet element. */
    public function toCssVariables(): array
    {
        $variables = [
            '--fq-cols' => (string) $this->columns,
            '--fq-label-width' => $this->labelWidth,
            '--fq-label-height' => $this->labelHeight,
            '--fq-column-gap' => $this->columnGap,
            '--fq-row-gap' => $this->rowGap,
            '--fq-margin-top' => $this->marginTop,
            '--fq-margin-right' => $this->marginRight,
            '--fq-margin-bottom' => $this->marginBottom,
            '--fq-margin-left' => $this->marginLeft,
        ];

        if ($this->rows !== null) {
            $variables['--fq-rows'] = (string) $this->rows;
        }

        if ($this->pageWidth !== '') {
            $variables['--fq-page-width'] = $this->pageWidth;
        }

        if ($this->pageHeight !== '') {
            $variables['--fq-page-height'] = $this->pageHeight;
        }

        return $variables;
    }

    /** `toCssVariables()` as an inline `style` attribute value. */
    public function cssStyle(): string
    {
        $parts = [];

        foreach ($this->toCssVariables() as $name => $value) {
            $parts[] = "{$name}: {$value}";
        }

        return implode('; ', $parts);
    }
}
