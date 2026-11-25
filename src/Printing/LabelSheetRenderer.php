<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Printing;

use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

/**
 * Turn a list of labels into paged HTML the print stylesheet and the
 * documented PDF recipe both consume.
 */
final class LabelSheetRenderer
{
    protected int $offset = 0;

    protected QrFormat $format = QrFormat::Svg;

    /** Bind this renderer to a sheet layout. */
    public function __construct(
        protected LabelSheet $sheet,
    ) {}

    /** Start a renderer for this sheet. */
    public static function make(LabelSheet $sheet): self
    {
        return new self($sheet);
    }

    /** Skip this many cells on the first page, for a partly used Avery sheet. */
    public function offset(int $cells): self
    {
        $this->offset = max(0, $cells);

        return $this;
    }

    /** Rasterize symbols as PNG when the consumer cannot paint SVG (dompdf). */
    public function format(QrFormat $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * The paged sheet markup only, without a surrounding HTML document.
     *
     * @param  list<Label>  $labels
     */
    public function toHtml(array $labels): string
    {
        return view('filament-qr-code::print.labels', [
            'sheet' => $this->sheet,
            'pages' => $this->pages($labels),
            'format' => $this->format,
        ])->render();
    }

    /**
     * A standalone HTML document with the package print CSS inlined, suitable
     * for a print window or a PDF engine.
     *
     * @param  list<Label>  $labels
     */
    public function toDocument(array $labels, ?string $title = null): string
    {
        return view('filament-qr-code::print.document', [
            'title' => $title ?? (string) __('filament-qr-code::ui.print_labels'),
            'css' => $this->printCss(),
            'pageSize' => $this->sheet->pageSize,
            'pageWidth' => $this->sheet->pageWidth,
            'pageHeight' => $this->sheet->pageHeight,
            'sheetHtml' => $this->toHtml($labels),
        ])->render();
    }

    /**
     * Split labels into pages, honouring `offset` and padding the last Avery page.
     *
     * @param  list<Label>  $labels
     * @return list<list<Label|null>>
     */
    public function pages(array $labels): array
    {
        $cells = [];

        for ($i = 0; $i < $this->offset; $i++) {
            $cells[] = null;
        }

        foreach ($labels as $label) {
            $cells[] = $label;
        }

        $perPage = $this->sheet->labelsPerPage();

        if ($perPage === null || $perPage < 1) {
            return [$cells];
        }

        $remainder = count($cells) % $perPage;

        if ($remainder !== 0) {
            $cells = [...$cells, ...array_fill(0, $perPage - $remainder, null)];
        }

        return array_chunk($cells, $perPage);
    }

    /** The published package CSS, inlined into `toDocument()`. */
    public function printCss(): string
    {
        $path = dirname(__DIR__, 2).'/resources/dist/filament-qr-code.css';

        if (! is_file($path)) {
            return '';
        }

        $css = file_get_contents($path);

        return $css === false ? '' : $css;
    }
}
