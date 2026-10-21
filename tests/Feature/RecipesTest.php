<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\View\ViewManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Smoke check for docs/recipes: every php block lints and only calls methods
 * that exist on the package, the Filament/Laravel classes recipes build on,
 * or the recipe's own classes. Catches renamed or removed API in docs.
 */
function recipeBlocks(): array
{
    $blocks = [];

    foreach (glob(__DIR__.'/../../docs/recipes/*.md') ?: [] as $file) {
        preg_match_all('/```php\n(.*?)```/s', (string) file_get_contents($file), $matches);

        foreach ($matches[1] as $index => $code) {
            $blocks[basename($file).' #'.($index + 1)] = $code;
        }
    }

    return $blocks;
}

function lintsAsPhp(string $code): bool
{
    $file = tempnam(sys_get_temp_dir(), 'recipe');
    file_put_contents($file, $code);
    exec(PHP_BINARY.' -l '.escapeshellarg($file).' 2>&1', $output, $exit);
    unlink($file);

    return $exit === 0;
}

/** @return array<string, true> lower-cased method names */
function knownRecipeMethods(): array
{
    static $methods = null;

    if ($methods !== null) {
        return $methods;
    }

    $classes = [Action::class, ColorPicker::class, Repeater::class, Select::class, TextInput::class, Notification::class,
        Page::class, Schema::class, Builder::class, Illuminate\Database\Query\Builder::class, Model::class, Collection::class, LoggerInterface::class, ViewManager::class];

    foreach (Finder::create()->files()->in(__DIR__.'/../../src')->name('*.php') as $file) {
        $class = 'Mmuqiitf\\FilamentQrCode\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

        if (class_exists($class) || trait_exists($class) || interface_exists($class)) {
            $classes[] = $class;
        }
    }

    $methods = [];

    foreach ($classes as $class) {
        foreach ((new ReflectionClass($class))->getMethods() as $method) {
            $methods[strtolower($method->getName())] = true;
        }
    }

    // Methods the recipes define themselves.
    foreach (recipeBlocks() as $code) {
        preg_match_all('/function\s+(\w+)\s*\(/', $code, $defined);

        foreach ($defined[1] as $name) {
            $methods[strtolower($name)] = true;
        }
    }

    return $methods;
}

it('has recipes', function (): void {
    expect(recipeBlocks())->not->toBeEmpty();
});

it('lints every php block in the recipes', function (string $code): void {
    preg_match_all('/^use [^;]+;\n/m', $code, $uses);
    $body = preg_replace('/^use [^;]+;\n/m', '', $code);
    $head = "<?php\n".implode('', $uses[0]);

    $candidates = str_starts_with(ltrim($code), '<?php')
        ? [$code]
        : [$head.$body, $head."return [\n".$body."\n];", $head."final class RecipeFragment\n{\n".$body."\n}"];

    expect(collect($candidates)->contains(fn (string $candidate): bool => lintsAsPhp($candidate)))
        ->toBeTrue('Recipe block does not lint as a file, an array fragment or a class body');
})->with(fn (): array => recipeBlocks());

it('only imports package classes that exist', function (string $code): void {
    preg_match_all('/^use (Mmuqiitf\\\\FilamentQrCode\\\\[^;\s]+);/m', $code, $imports);
    expect($imports[1])->toBeArray();

    foreach ($imports[1] as $class) {
        expect(class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class))
            ->toBeTrue("{$class} does not exist");
    }
})->with(fn (): array => recipeBlocks());

it('only calls methods that exist', function (string $code): void {
    preg_match_all('/(?:->|::)(\w+)\(/', $code, $calls);
    $known = knownRecipeMethods();

    $unknown = array_values(array_unique(array_filter($calls[1], fn (string $method): bool => ! isset($known[strtolower($method)]))));

    expect($unknown)->toBe([], 'Unknown methods: '.implode(', ', $unknown));
})->with(fn (): array => recipeBlocks());
