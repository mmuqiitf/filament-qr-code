<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Printing\Label;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheet;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheetRenderer;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheetStore;

trait HasLabelSheet
{
    protected LabelSheet|Closure|null $labelSheet = null;

    protected string|Closure|null $qrData = null;

    protected string|Closure|null $barcodeData = null;

    protected BarcodeType|string $barcodeType = BarcodeType::Code128;

    /** @var Closure|array<int|string, mixed>|null */
    protected Closure|array|null $digitalLink = null;

    protected string|Closure|null $captionUsing = null;

    protected string|Closure|null $headingUsing = null;

    protected ?Closure $labelUsing = null;

    protected int|Closure $copies = 1;

    protected int|Closure $sheetOffset = 0;

    protected QrFormat|Closure $sheetFormat = QrFormat::Svg;

    /** The Avery or custom sheet laid out in the print page. */
    public function sheet(LabelSheet|Closure $sheet): static
    {
        $this->labelSheet = $sheet;

        return $this;
    }

    /** Value encoded in the QR; a closure receives the record. */
    public function qrData(string|Closure $data): static
    {
        $this->qrData = $data;

        return $this;
    }

    /** Value and symbology for the barcode beside the QR; a closure receives the record. */
    public function barcodeData(string|Closure $data, BarcodeType|string $type = BarcodeType::Code128): static
    {
        $this->barcodeData = $data;
        $this->barcodeType = $type;

        return $this;
    }

    /**
     * @param  Closure|array<int|string, mixed>  $elements
     */
    /** Encode a GS1 Digital Link URI as the QR payload. A closure receives the record. */
    public function digitalLink(Closure|array $elements): static
    {
        $this->digitalLink = $elements;

        return $this;
    }

    /** Caption under the symbols; a closure receives the record. */
    public function caption(string|Closure $caption): static
    {
        $this->captionUsing = $caption;

        return $this;
    }

    /** Heading above the symbols; a closure receives the record. */
    public function heading(string|Closure $heading): static
    {
        $this->headingUsing = $heading;

        return $this;
    }

    /** Build the label yourself; the closure receives the record and must return a Label. */
    public function labelUsing(Closure $callback): static
    {
        $this->labelUsing = $callback;

        return $this;
    }

    /** Print each record this many times (default 1). */
    public function copies(int|Closure $copies): static
    {
        $this->copies = $copies;

        return $this;
    }

    /** Skip this many cells on the first page, for a partly used Avery sheet. */
    public function offset(int|Closure $cells): static
    {
        $this->sheetOffset = $cells;

        return $this;
    }

    /** Symbol format; use PNG when a PDF engine cannot paint SVG. */
    public function format(QrFormat|Closure $format): static
    {
        $this->sheetFormat = $format;

        return $this;
    }

    public function getSheet(): LabelSheet
    {
        $sheet = $this->labelSheet !== null
            ? $this->evaluate($this->labelSheet)
            : null;

        return $sheet instanceof LabelSheet ? $sheet : LabelSheet::avery5160();
    }

    /**
     * Build a label for every record that has data, repeating each one `copies` times.
     *
     * @param  iterable<mixed>  $records
     * @return list<Label>
     */
    public function labelsForRecords(iterable $records): array
    {
        $labels = [];
        $copies = max(1, (int) $this->evaluate($this->copies));

        foreach ($records as $record) {
            $label = $this->labelForRecord($record);

            if ($label === null || $label->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < $copies; $i++) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /** One label for a record, or null when it has no QR or barcode data. */
    public function labelForRecord(mixed $record): ?Label
    {
        if ($this->labelUsing !== null) {
            $label = $this->evaluate($this->labelUsing, ['record' => $record]);

            return $label instanceof Label && ! $label->isEmpty() ? $label : null;
        }

        $label = Label::make()
            ->caption($this->resolveString($this->captionUsing, $record))
            ->heading($this->resolveString($this->headingUsing, $record));

        $elements = $this->resolveDigitalLink($record);

        if ($elements !== null) {
            $label->digitalLink($elements);
        } else {
            $label->qr($this->resolveQrData($record));
        }

        if ($this->barcodeData !== null) {
            $type = $this->barcodeType instanceof BarcodeType
                ? $this->barcodeType
                : BarcodeType::from($this->barcodeType);

            $label->barcode($this->resolveString($this->barcodeData, $record), $type);
        }

        return $label->isEmpty() ? null : $label;
    }

    /**
     * Render the sheet, store it behind a short-lived token, and return the print URL.
     *
     * @param  iterable<mixed>  $records
     */
    public function storePrint(iterable $records): ?string
    {
        $labels = $this->labelsForRecords($records);

        if ($labels === []) {
            return null;
        }

        $format = $this->evaluate($this->sheetFormat);
        $format = $format instanceof QrFormat ? $format : QrFormat::Svg;
        $offset = max(0, (int) $this->evaluate($this->sheetOffset));

        $html = LabelSheetRenderer::make($this->getSheet())
            ->format($format)
            ->offset($offset)
            ->toDocument($labels);

        $token = LabelSheetStore::put($html);

        return url('filament-qr-code/labels/'.$token);
    }

    protected function resolveQrData(mixed $record): ?string
    {
        if ($this->qrData !== null) {
            return $this->resolveString($this->qrData, $record);
        }

        if (! $record instanceof Model) {
            return $this->scalarAttribute($record, 'sku')
                ?? $this->scalarAttribute($record, 'qr_code')
                ?? $this->scalarAttribute($record, 'code');
        }

        foreach (['qr_code', 'code', 'sku'] as $key) {
            $value = $record->getAttribute($key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @return ElementString|array<int|string, mixed>|null
     */
    protected function resolveDigitalLink(mixed $record): ElementString|array|null
    {
        if ($this->digitalLink === null) {
            return null;
        }

        if (is_array($this->digitalLink)) {
            return $this->digitalLink;
        }

        $elements = $this->evaluate($this->digitalLink, ['record' => $record]);

        if ($elements instanceof ElementString) {
            return $elements;
        }

        return is_array($elements) && $elements !== [] ? $elements : null;
    }

    protected function resolveString(string|Closure|null $value, mixed $record): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Closure) {
            $resolved = $this->evaluate($value, ['record' => $record]);

            return is_scalar($resolved) && trim((string) $resolved) !== '' ? (string) $resolved : null;
        }

        return $this->scalarAttribute($record, $value);
    }

    protected function scalarAttribute(mixed $record, string $key): ?string
    {
        $attribute = $record instanceof Model
            ? $record->getAttribute($key)
            : data_get($record, $key);

        return is_scalar($attribute) && trim((string) $attribute) !== '' ? (string) $attribute : null;
    }
}
