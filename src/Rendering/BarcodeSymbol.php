<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Com\Tecnick\Barcode\Barcode;
use Com\Tecnick\Barcode\Exception as TcBarcodeException;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Gs1\CheckDigit;

/**
 * A validated, encoded barcode in module units: the bars to draw and the
 * human-readable text. Encoding is delegated to tecnickcom/tc-lib-barcode.
 *
 * @internal Not an extension point; may change without notice. Use BarcodeService.
 */
final readonly class BarcodeSymbol
{
    /**
     * @param  list<array{int, int, int, int}>  $bars  x, y, width, height in modules
     */
    private function __construct(
        public array $bars,
        public int $columns,
        public int $rows,
        public string $text,
    ) {}

    /**
     * @throws InvalidBarcodeException
     */
    public static function encode(BarcodeType $type, string $value): self
    {
        self::validate($type, $value);

        try {
            $model = (new Barcode)->getBarcodeObj(self::libraryType($type), $value, -1, -1);
        } catch (TcBarcodeException $e) {
            throw new InvalidBarcodeException($type, $value, self::reason($e->getMessage()), $e);
        }

        $array = $model->getArray();
        $bars = array_values(array_map(
            fn (array $bar): array => [(int) $bar[0], (int) $bar[1], (int) $bar[2], (int) $bar[3]],
            $array['bars'],
        ));

        return new self($bars, (int) $array['ncols'], (int) $array['nrows'], self::text($type, $model->getExtendedCode()));
    }

    private static function validate(BarcodeType $type, string $value): void
    {
        if ($value === '') {
            throw new InvalidBarcodeException($type, $value, 'the value is empty');
        }

        $lengths = $type->getDigitLengths();
        if ($lengths !== null) {
            if (! ctype_digit($value)) {
                throw new InvalidBarcodeException($type, $value, 'only digits are allowed');
            }

            if (! in_array(strlen($value), $lengths, true)) {
                throw new InvalidBarcodeException($type, $value, "expected {$lengths[0]} or {$lengths[1]} digits, got ".strlen($value));
            }

            if (strlen($value) === $lengths[1] && ! CheckDigit::validate($value)) {
                throw new InvalidBarcodeException($type, $value, 'wrong check digit, expected '.CheckDigit::calculate(substr($value, 0, -1)));
            }

            return;
        }

        if ($type === BarcodeType::Code39 && preg_match('/^[0-9A-Z \-.$\/+%]+$/', $value) !== 1) {
            throw new InvalidBarcodeException($type, $value, 'Code 39 allows only 0-9, A-Z (uppercase), space and - . $ / + %');
        }

        if ($type === BarcodeType::Code128 && preg_match('/^[\x00-\x7F]+$/', $value) !== 1) {
            throw new InvalidBarcodeException($type, $value, 'Code 128 allows only ASCII characters');
        }
    }

    private static function libraryType(BarcodeType $type): string
    {
        return match ($type) {
            BarcodeType::Code128 => 'C128',
            BarcodeType::Ean13 => 'EAN13',
            BarcodeType::Ean8 => 'EAN8',
            BarcodeType::UpcA => 'UPCA',
            BarcodeType::Code39 => 'C39',
            BarcodeType::Itf14 => 'ITF14',
        };
    }

    private static function text(BarcodeType $type, string $extended): string
    {
        return match ($type) {
            // tc-lib-barcode stores UPC-A as its EAN-13 form (leading 0).
            BarcodeType::UpcA => strlen($extended) === 13 ? substr($extended, 1) : $extended,
            BarcodeType::Code39 => trim($extended, '*'),
            default => $extended,
        };
    }

    private static function reason(string $message): string
    {
        $message = rtrim($message, '.');

        return $message === '' ? 'the value can\'t be encoded' : lcfirst($message);
    }
}
