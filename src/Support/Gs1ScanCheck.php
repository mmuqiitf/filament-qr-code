<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\CheckDigit;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;
use Mmuqiitf\FilamentQrCode\Gs1\SyntaxTable;

/**
 * The `->gs1()` check shared by QrScanner and QrHardwareScannerListener:
 * parse a scan as a GS1 element string, turn failures into a translated
 * message, and map application identifiers onto form fields.
 *
 * @internal Use QrScanner::gs1() or QrHardwareScannerListener::gs1().
 */
final readonly class Gs1ScanCheck
{
    /** AIM symbology identifiers of plain EAN/UPC and ITF symbols, which cannot carry GS1 AIs. */
    private const PLAIN_SYMBOLOGIES = [']E', ']I'];

    /** GTIN-8, UPC-A, EAN-13 and ITF-14 lengths. */
    private const PLAIN_LENGTHS = [8, 12, 13, 14];

    /**
     * @param  array<int|string, string>  $fill  AI => relative state path (PHP turns keys like '10' into ints)
     * @param  list<string>  $require  AIs every scan must carry
     */
    public function __construct(
        public array $fill = [],
        public array $require = [],
    ) {}

    /**
     * Build from user input, rejecting AIs the GS1 Syntax Dictionary does not know.
     *
     * @param  array<int|string, mixed>  $fill
     * @param  array<int|string, mixed>  $require
     *
     * @throws InvalidArgumentException
     */
    public static function from(array $fill, array $require): self
    {
        $map = [];

        foreach ($fill as $ai => $path) {
            $ai = self::knownAi((string) $ai, 'fill');

            if (! is_string($path) || $path === '') {
                throw new InvalidArgumentException("gs1(fill:) needs a field name for AI ({$ai}).");
            }

            $map[$ai] = $path;
        }

        $required = [];

        foreach ($require as $ai) {
            $required[] = self::knownAi(is_scalar($ai) ? (string) $ai : '', 'require');
        }

        return new self($map, array_values(array_unique($required)));
    }

    /**
     * The parsed scan, or the translated reason it is rejected.
     */
    public function inspect(string $code): ElementString|string
    {
        if (self::isPlainBarcode($code)) {
            return (string) __('filament-qr-code::ui.gs1_plain_barcode');
        }

        try {
            $elements = Gs1::parse($code);
        } catch (InvalidGs1Exception $exception) {
            return self::message($exception);
        }

        $present = $elements->toArray();

        foreach ($this->require as $ai) {
            if (! array_key_exists($ai, $present)) {
                return (string) __('filament-qr-code::ui.gs1_missing_ai', [
                    'ai' => $ai,
                    'title' => SyntaxTable::find($ai)->title ?? '',
                ]);
            }
        }

        return $elements;
    }

    /**
     * Values for the mapped fields: dates as `Y-m-d`, implied-decimal AIs as
     * exact decimal strings, anything else raw. AIs absent from the scan are
     * left out.
     *
     * @return array<string, string> state path => value
     */
    public function fillValues(ElementString $elements): array
    {
        $values = [];

        foreach ($this->fill as $ai => $path) {
            $ai = (string) $ai;
            $value = $elements->get($ai);

            if ($value === null) {
                continue;
            }

            $values[$path] = $elements->date($ai)?->format('Y-m-d')
                ?? $elements->decimal($ai)
                ?? $value;
        }

        return $values;
    }

    /** A translated, field-ready message for a parse failure. */
    public static function message(InvalidGs1Exception $exception): string
    {
        $ai = $exception->ai;

        if ($ai === null) {
            return (string) __('filament-qr-code::ui.gs1_not_gs1');
        }

        $message = match ($exception->kind) {
            InvalidGs1Exception::UNKNOWN_AI => __('filament-qr-code::ui.gs1_unknown_ai', ['ai' => $ai]),
            InvalidGs1Exception::DUPLICATE_AI => __('filament-qr-code::ui.gs1_duplicate_ai', ['ai' => $ai]),
            InvalidGs1Exception::LENGTH => __('filament-qr-code::ui.gs1_invalid_length', ['ai' => $ai]),
            InvalidGs1Exception::CHARACTER_SET => __('filament-qr-code::ui.gs1_invalid_characters', ['ai' => $ai]),
            InvalidGs1Exception::CHECK_DIGIT => __('filament-qr-code::ui.gs1_invalid_check_digit', ['ai' => $ai]),
            InvalidGs1Exception::DATE => __('filament-qr-code::ui.gs1_invalid_date', ['ai' => $ai]),
            default => __('filament-qr-code::ui.gs1_invalid_format', ['ai' => $ai]),
        };

        return (string) $message;
    }

    /**
     * A plain EAN/UPC or ITF-14 number parses as a GS1 string by accident
     * (`4006381333931` reads as AI 400), so it is told apart first: an EAN,
     * UPC or ITF symbology identifier, or a bare 8, 12, 13 or 14-digit number
     * with a valid check digit.
     */
    public static function isPlainBarcode(string $code): bool
    {
        $code = trim($code);

        if (in_array(substr($code, 0, 2), self::PLAIN_SYMBOLOGIES, true)) {
            return true;
        }

        return ctype_digit($code)
            && in_array(strlen($code), self::PLAIN_LENGTHS, true)
            && CheckDigit::validate($code);
    }

    private static function knownAi(string $ai, string $argument): string
    {
        if (SyntaxTable::find($ai) === null) {
            throw new InvalidArgumentException("gs1({$argument}:) names unknown application identifier ({$ai}).");
        }

        return $ai;
    }
}
