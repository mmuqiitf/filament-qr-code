# Recipe: work-order station

An assembly station: the operator scans their badge, a work order, a rework reason when the order is a rework, then one label per component until the unit is built. It uses every P2 extension point: a non-Eloquent lookup, a conditional step, a custom check stage, a whole-set check, step hooks and runtime control.

Runs in the example app: `/admin/manufacturing` (`app/Filament/Pages/ManufacturingStation.php`).

## The sequence

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

QrScanSequence::make()
    ->statePath('station')
    ->steps([
        ScanStep::make('operator')
            ->label('Operator badge')
            ->validateUsing(['regex:/^OP-\d{3}$/'])
            ->lookupUsing(fn (string $code): ?array => app(BadgeDirectory::class)->find($code)) // not Eloquent
            ->sticky(),
        ScanStep::make('order')
            ->label('Work order')
            ->lookup(AssemblyOrder::class, 'number', fn ($query) => $query->where('status', 'open'))
            ->sticky(),
        ScanStep::make('rework_reason')
            ->label('Rework reason')
            ->when(fn (array $values): bool => (bool) AssemblyOrder::findOpen($values['order'] ?? null)?->is_rework)
            ->validateUsing(['regex:/^RW-\d{2}$/']),
        ScanStep::make('components')
            ->label('Components')
            ->validateUsing(['regex:/^CMP-\d{3}:\d{4}$/'])   // component SKU, then serial
            ->repeat()
            ->finishWith('DONE')
            ->pipe(ComponentBelongsToBom::class),
    ])
    ->normalizeWith(uppercase: true)
    ->validateAllUsing(fn (array $values): array => ['components' => $this->missingComponents($values)])
    ->onStepAccepted(fn (string $step, ScanResult $result) => $this->logActivity($step, $result))
    ->onStepRejected(fn (string $step, ScanResult $result) => $this->logActivity($step, $result))
    ->autoSubmit()
    ->resetAfterSubmit()
    ->editable(false),
```

## A lookup that isn't Eloquent

`lookupUsing()` replaces the model lookup. Return anything (here an array) or `null` to reject with the "no record" message. The value reaches `ScanResult::$record`, your hooks and your stages:

```php
final class BadgeDirectory
{
    /** @return array{badge: string, name: string, active: bool}|null */
    public function find(string $badge): ?array
    {
        $operator = Http::get("https://hr.example.test/badges/{$badge}")->json();

        return ($operator['active'] ?? false) ? $operator : null;
    }

    /** @return list<array{badge: string, name: string, active: bool}> */
    public function active(): array
    {
        return Http::get('https://hr.example.test/badges', ['active' => 1])->json();
    }
}
```

The work order model keeps the BOM lookups the stage and the page share:

```php
final class AssemblyOrder extends Model
{
    public function bomLines(): HasMany
    {
        return $this->hasMany(BomLine::class);
    }

    public static function findOpen(?string $number): ?self
    {
        return $number === null ? null : self::query()->where('number', $number)->where('status', 'open')->first();
    }

    /** @return array<string, int> component SKU => quantity per unit */
    public function bom(): array
    {
        return $this->bomLines()->pluck('quantity', 'sku')->map(fn ($quantity): int => (int) $quantity)->all();
    }
}
```

## A check stage

Stages run after the step's rules, its lookup and its `validateUsing()` closures, and again on submit. Return `true`/`null` to pass, `false` for the default message, or a string message:

```php
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;

final class ComponentBelongsToBom implements ScanCheckStage
{
    public function check(ScanCheckContext $context): bool|string|null
    {
        $order = AssemblyOrder::findOpen($context->values['order'] ?? null);

        if ($order === null) {
            return 'Scan a work order first.';
        }

        $sku = explode(':', $context->code, 2)[0];
        $needed = $order->bom()[$sku] ?? 0;

        if ($needed === 0) {
            return "{$sku} is not on the BOM for {$order->number}.";
        }

        // Live scans see the codes collected so far; submit re-runs see the
        // full list, this code included. Count the others either way.
        $fitted = count(array_filter(
            (array) ($context->values['components'] ?? []),
            fn (mixed $code): bool => $code !== $context->code && explode(':', (string) $code, 2)[0] === $sku,
        ));

        return $fitted < $needed ? true : "All {$needed} × {$sku} are already fitted.";
    }
}
```

Stages are resolved from the container, so constructor injection works. To add one to every sequence step or scanner in the app, use `configureUsing()` in a service provider (there is no config list):

```php
QrScanner::configureUsing(fn (QrScanner $scanner) => $scanner->pipe(RejectBlockedCodes::class));
```

## The whole BOM

`components` is open-ended: the operator scans `DONE` (or presses the step's **Done** button) when the unit is assembled. Finishing runs `validateAllUsing()`:

```php
public function missingComponents(array $values): ?string
{
    $order = AssemblyOrder::findOpen($values['order'] ?? null);
    $fitted = array_count_values(array_map(fn ($code) => explode(':', (string) $code, 2)[0], $values['components'] ?? []));

    $missing = [];

    foreach ($order?->bom() ?? [] as $sku => $quantity) {
        if (($short = $quantity - ($fitted[$sku] ?? 0)) > 0) {
            $missing[] = "{$short} × {$sku}";
        }
    }

    return $missing === [] ? null : 'BOM incomplete: missing '.implode(', ', $missing).'.';
}
```

`validateAllUsing()` also runs when a counted step (`repeat(3)`) is finished early with `finishWith()`: the operator sees what the BOM is missing ("BOM incomplete: missing …") instead of the generic repeat count message, and `qr-sequence-invalid` fires.

## Hooks and runtime control

`onStepAccepted()` / `onStepRejected()` receive `$step`, `$result`, `$code`, `$record`, `$source` and `$values`, and can't change the outcome:

```php
public function logActivity(string $step, ScanResult $result): void
{
    $text = match (true) {
        ! $result->passes => "{$result->code}: {$result->message}",
        $step === 'operator' && is_array($result->record) => "{$result->record['name']} signed in",
        default => "{$step}: {$result->code}",
    };

    array_unshift($this->activity, ['step' => $step, 'text' => $text, 'rejected' => ! $result->passes]);
}

public function restartUnit(): void { $this->sequence()->resetSequence(); }                    // keeps badge and order
public function changeOperator(): void { $this->sequence()->resetSequence(keepSticky: false); }
public function backToComponents(): void { $this->sequence()->goToStep('components'); }
```

```blade
<x-filament::button wire:click="restartUnit">Restart unit</x-filament::button>
<x-filament::button x-on:click="$dispatch('qr-sequence-pause', { field: 'data.station' })">Pause scanning</x-filament::button>
<x-filament::button x-on:click="$dispatch('qr-sequence-resume', { field: 'data.station' })">Resume</x-filament::button>
```

## How it behaves

- `op-001` is cleaned to `OP-001` in the browser and on the server (`normalizeWith()`), then found in the directory.
- Badge and work order are sticky: after a build the next unit starts at the first empty step.
- On a rework order the rework step becomes active; on other orders it is skipped, greyed out and not required.
- A component that isn't on the BOM, one too many, or a duplicate serial is rejected with its message; scanning continues.
- `DONE` with parts missing jumps back to components with "BOM incomplete: …". A complete unit auto-submits.
