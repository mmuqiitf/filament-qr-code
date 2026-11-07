# GS1 element strings

> Back to [README](../README.md) · [Barcodes](barcodes.md) · [Reference](reference.md)

`Gs1::parse()` and `Gs1::build()` read and write a GS1 element string: the application identifiers (AIs) inside a GS1-128, GS1 DataMatrix or GS1 QR symbol. This does not draw a barcode. GS1-128 rendering is still unsupported.

Length and character set follow the [GS1 Barcode Syntax Dictionary](https://ref.gs1.org/tools/gs1-barcode-syntax-resource/syntax-dictionary/) release 2026-01-27, vendored at `resources/gs1/` under the Apache License 2.0. Check digits, dates, country codes and "this AI requires that AI" rules are not applied yet.

## Parse

```php
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

$gs1 = Gs1::parse('(01)09521101530001(17)251231(10)LOT-1');

$gs1->get('01');       // '09521101530001'
$gs1->get('10');       // 'LOT-1'
$gs1->toArray();       // ['01' => '...', '17' => '251231', '10' => 'LOT-1']
$gs1->bracketed();     // '(01)09521101530001(17)251231(10)LOT-1'
$gs1->raw();           // unbracketed, with a group separator where one is required
$gs1->elements()[0]->title; // 'GTIN'
```

The same value comes back from three inputs:

- Bracketed, the form printed under a symbol: `(01)09521101530001(10)LOT-1`.
- Unbracketed scan data. Fixed-length AIs are concatenated. A variable-length AI that is not last is followed by `Gs1::SEPARATOR` (ASCII 29, the character scanners return for FNC1). A separator after a fixed-length AI, or a trailing one, is ignored.
- The same scan data with an AIM symbology identifier in front: `]C1` (GS1-128), `]e0` (GS1 DataBar), `]d2` (GS1 DataMatrix) or `]Q3` (GS1 QR). Any `]` + one letter + one digit is stripped.

`(` inside a bracketed value starts the next AI. A value that itself contains `(` has to be parsed from the unbracketed form.

## Build

```php
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

$gs1 = Gs1::build([
    '01' => '09521101530001',
    '10' => 'LOT-1',
    '17' => '251231',
]);

$gs1->raw(); // "010952110153000110LOT-1\x1D17251231"
```

Order is the order of the array. Write every key as a string. `'01'` and `'00'` keep their leading zero only as strings. PHP turns `'10'` and `'8003'` into integer keys on its own; `build()` casts those back.

## Invalid values

`parse()` and `build()` throw `InvalidGs1Exception`. The message names the AI and the reason, for example a GTIN that is not 14 digits, a batch with a character outside GS1 character set 82, an unknown AI, or the same AI twice. `reason` is the part after the colon.

```php
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

try {
    Gs1::parse($scan);
} catch (InvalidGs1Exception $e) {
    $e->reason;
}
```
