# GS1 element strings

> Back to [README](../README.md) · [Barcodes](barcodes.md) · [Reference](reference.md)

`Gs1::parse()` and `Gs1::build()` read and write a GS1 element string: the application identifiers (AIs) inside a GS1-128, GS1 DataMatrix or GS1 QR symbol. This does not draw a barcode. GS1-128 rendering is still unsupported.

Length and character set follow the [GS1 Barcode Syntax Dictionary](https://ref.gs1.org/tools/gs1-barcode-syntax-resource/syntax-dictionary/) release 2026-01-27, vendored at `resources/gs1/` under the Apache License 2.0. So do [check digits](#check-digit) and [dates](#dates): both are always enforced. Unknown AIs are rejected. Country and currency codes, times and "this AI requires that AI" rules are not applied yet.

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

## Drawing GS1-128

`BarcodeType::Gs1128` (`type('gs1-128')`) draws the element string as a GS1-128 symbol. The human-readable text is the bracketed form. A raw scan, a `]C1` prefix and the bracketed string encode the same bars. The symbol holds at most 48 data characters. Check digits are not added: a wrong one is rejected like any other invalid element string.

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;

$barcode = BarcodeService::make()
    ->type(BarcodeType::Gs1128)
    ->generate('(01)09521101530001(17)251231(10)LOT-1');

$barcode->getText(); // '(01)09521101530001(17)251231(10)LOT-1'
```

## Check digit

`CheckDigit` is the GS1 mod-10 digit on a GTIN, SSCC, GLN, GDTI or GSRN. EAN-13, EAN-8, UPC-A and ITF-14 use the same digit. Pass the number, not a whole element string. The GMN alphanumeric check character (AI 8013) is a different algorithm and is not checked.

`Gs1::parse()` and `Gs1::build()` always check the digit on every component the dictionary marks `csum`: SSCC (00), GTIN (01, 02, 03), the GLNs (410 to 417), GDTI (253), GCN (255), GSIN (402), GRAI (8003), ITIP (8006, 8026) and GSRN (8017, 8018). There is no opt-out. A wrong digit throws `InvalidGs1Exception` with the digit that was expected, for example `AI 01 (GTIN) has an invalid check digit, expected 1`.

```php
use Mmuqiitf\FilamentQrCode\Gs1\CheckDigit;
use Mmuqiitf\FilamentQrCode\Validation\ValidCheckDigit;

CheckDigit::calculate('400638133393'); // 1
CheckDigit::validate('4006381333931'); // true

TextInput::make('gtin')->rule(new ValidCheckDigit);
```

`calculate()` throws `InvalidArgumentException` when the input is empty or not all digits. `validate()` returns false for that, and when the last digit is wrong.

## Dates

Date fields are checked against the calendar: `(17)261399` and `(15)260230` throw `AI 17 (USE BY or EXPIRY) is not a valid date`. The value stays the string that was scanned. `date()` reads it as a `CarbonImmutable` at the start of the day, in the app's timezone.

```php
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

$gs1 = Gs1::parse('(01)09521101530001(17)261200(10)LOT-1');

$gs1->get('17');   // '261200'
$gs1->date('17');  // CarbonImmutable 2026-12-31 00:00:00
$gs1->date('10');  // null: not a date field
$gs1->elements()[1]->date(); // the same date from the Element
```

- Day `00` means the last day of the month on the AIs that allow it (11, 12, 13, 15, 16, 17, 4324, 4325). Elsewhere, such as 4326, 7003, 7006 and 7007, the day has to be real.
- A two-digit year uses the GS1 sliding window: from the current year, 51 to 99 years ahead is the previous century and 50 to 99 years behind is the next one. In 2026, `76` is 2076 and `77` is 1977. 29 February is checked against that year.
- AI 7250 and 7251 carry a four-digit year.
- Only the first date is returned: the start of a 7007 harvest range, and the date part of 7003, 7011, 7251, 8008, 4324 and 4325. The time stays in the value and is not checked yet.

## Decimals

Measures and amounts carry an implied decimal point: the last digit of the AI is the number of decimal places. `decimal()` returns the exact value as a string. It never goes through a float, so nothing is rounded.

```php
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

$gs1 = Gs1::parse('(01)09521101530001(3103)001250(3922)1999');

$gs1->get('3103');     // '001250'
$gs1->decimal('3103'); // '1.250' kg
$gs1->decimal('3922'); // '19.99'
$gs1->decimal('01');   // null: no implied decimal point
```

`decimal()` covers 310n to 369n (trade and logistic measures) and 390n to 395n (amounts, prices, percentage off, price per unit). For 391n and 393n the first three digits are the ISO 4217 currency code and are left out: `(3912)9781999` is `'19.99'`, and `substr($gs1->get('3912'), 0, 3)` is the currency. Leading zeros of the whole part are dropped, the places are kept: `(3102)000000` is `'0.00'`. Pass the string to `bcmath` or a money library to calculate with it.

## Invalid values

`parse()` and `build()` throw `InvalidGs1Exception`. The message names the AI and the reason, for example a GTIN that is not 14 digits or has the wrong check digit, an expiry date that is not on the calendar, a batch with a character outside GS1 character set 82, an unknown AI, or the same AI twice. `reason` is the part after the colon.

```php
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

try {
    Gs1::parse($scan);
} catch (InvalidGs1Exception $e) {
    $e->reason;
}
```
