<?php

declare(strict_types=1);

/*
 * Builds docs/api.md (public API map + config keys) and llms-full.txt
 * (every user-facing doc in one file). Run with `composer docs`; CI checks
 * the committed files match (tests/Docs/ApiMapTest.php).
 */

namespace Mmuqiitf\FilamentQrCode\Bin;

use BackedEnum;
use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;
use UnitEnum;

const PACKAGE_NAMESPACE = 'Mmuqiitf\\FilamentQrCode\\';

/**
 * Consumer-facing classes in the order api.md lists them. Service
 * provider, controller, listener, render hook, commands and `@internal`
 * classes are left out on purpose.
 *
 * @return array<string, list<string>>
 */
function apiSections(): array
{
    return [
        'Plugin' => [
            'FilamentQrCodePlugin',
        ],
        'Scanning components' => [
            'Forms\\Components\\QrScanner',
            'Forms\\Components\\QrHardwareScannerListener',
            'Forms\\Components\\QrScanSequence',
            'Support\\ScanStep',
            'Forms\\Components\\QrCollector',
            'Tables\\Actions\\QrCollectAction',
        ],
        'Generating components' => [
            'Forms\\Components\\QrCodeDisplay',
            'Tables\\Columns\\QrColumn',
            'Infolists\\Components\\QrEntry',
            'Tables\\Actions\\DownloadQrAction',
            'Tables\\Actions\\DownloadQrBulkAction',
        ],
        'Services and helpers' => [
            'Facades\\FilamentQrCode',
            'Services\\QrCodeService',
            'Support\\QrPayload',
        ],
        'Server checks and validation' => [
            'Contracts\\ScanCheckStage',
            'Support\\ScanCheckContext',
            'Validation\\DistinctCodes',
            'Validation\\ScanChecksRule',
            'Validation\\SequenceSetRule',
            'Validation\\SequenceStepRule',
        ],
        'Events' => [
            'Events\\QrCodeScanned',
            'Events\\ScanAccepted',
            'Events\\ScanRejected',
            'Events\\ScanSequenceCompleted',
        ],
        'Enums' => [
            'Enums\\BarcodeFormat',
            'Enums\\ContinuousDisplay',
            'Enums\\HardwareInputScope',
            'Enums\\InvalidSequenceBehavior',
            'Enums\\OptionsSync',
            'Enums\\QrFormat',
            'Enums\\RepeatComplete',
            'Enums\\ScanInput',
            'Enums\\ScanMode',
        ],
    ];
}

/**
 * The user-facing docs llms-full.txt concatenates, in reading order.
 *
 * @return list<string>
 */
function llmsFullFiles(): array
{
    $docs = [
        'README.md',
        'docs/getting-started.md',
        'docs/scanning.md',
        'docs/collecting.md',
        'docs/generating.md',
        'docs/customizing.md',
        'docs/extending.md',
        'docs/events.md',
        'docs/reference.md',
        'docs/faq.md',
        'docs/ai.md',
    ];

    $recipes = glob(packageRoot().'/docs/recipes/*.md') ?: [];
    sort($recipes);

    foreach ($recipes as $recipe) {
        $docs[] = 'docs/recipes/'.basename($recipe);
    }

    return [...$docs, 'docs/api.md', 'UPGRADE.md'];
}

function packageRoot(): string
{
    return dirname(__DIR__);
}

function docSummary(string|false $docComment): string
{
    if ($docComment === false) {
        return '';
    }

    $lines = [];

    foreach (preg_split('/\R/', $docComment) ?: [] as $line) {
        $line = trim((string) preg_replace(['#^\s*(/\*\*|\*/|\*)\s?#', '#\s*\*/\s*$#'], '', $line));

        if (str_starts_with($line, '@')) {
            break;
        }

        if ($line === '') {
            if ($lines !== []) {
                break;
            }

            continue;
        }

        $lines[] = $line;
    }

    $summary = implode(' ', $lines);

    return str_replace('|', '\\|', $summary);
}

function isInternal(string|false $docComment): bool
{
    return $docComment !== false && str_contains($docComment, '@internal');
}

function typeString(?ReflectionType $type, ?string $self = null): string
{
    if ($type === null) {
        return '';
    }

    $name = (string) $type;

    return (string) preg_replace_callback(
        '/[\\\\\w]+/',
        fn (array $match): string => match (true) {
            $match[0] === 'self' && $self !== null => $self,
            str_contains($match[0], '\\') => ltrim(substr($match[0], (int) strrpos($match[0], '\\')), '\\'),
            default => $match[0],
        },
        $name,
    );
}

function exportValue(mixed $value): string
{
    return match (true) {
        $value === null => 'null',
        is_bool($value) => $value ? 'true' : 'false',
        is_string($value) => "'".$value."'",
        is_int($value), is_float($value) => (string) $value,
        $value instanceof UnitEnum => (new ReflectionClass($value))->getShortName().'::'.$value->name,
        is_array($value) && $value === [] => '[]',
        is_array($value) && array_is_list($value) => '['.implode(', ', array_map(exportValue(...), $value)).']',
        is_array($value) => '['.implode(', ', array_map(
            fn (int|string $key, mixed $item): string => exportValue($key).' => '.exportValue($item),
            array_keys($value),
            $value,
        )).']',
        default => '…',
    };
}

function parameterString(ReflectionParameter $parameter): string
{
    $out = typeString($parameter->getType());
    $out = $out === '' ? '' : $out.' ';
    $out .= ($parameter->isVariadic() ? '...' : '').'$'.$parameter->getName();

    if ($parameter->isDefaultValueAvailable()) {
        $out .= ' = '.($parameter->isDefaultValueConstant()
            ? (string) preg_replace('/^.*\\\\/', '', (string) $parameter->getDefaultValueConstantName())
            : exportValue($parameter->getDefaultValue()));
    }

    return $out;
}

function methodSignature(ReflectionMethod $method): string
{
    $parameters = implode(', ', array_map(parameterString(...), $method->getParameters()));
    $return = typeString($method->getReturnType(), $method->getDeclaringClass()->getShortName());

    return ($method->isStatic() ? 'static ' : '').$method->getName().'('.$parameters.')'.($return === '' ? '' : ': '.$return);
}

/**
 * Methods declared by a non-package ancestor are Filament or Laravel
 * overrides, not package API.
 */
function overridesFramework(ReflectionClass $class, string $method): bool
{
    foreach ($class->getInterfaces() as $interface) {
        if (! str_starts_with($interface->getName(), PACKAGE_NAMESPACE) && $interface->hasMethod($method)) {
            return true;
        }
    }

    for ($parent = $class->getParentClass(); $parent !== false; $parent = $parent->getParentClass()) {
        if (! str_starts_with($parent->getName(), PACKAGE_NAMESPACE) && $parent->hasMethod($method)) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<ReflectionMethod>
 */
function publicMethods(ReflectionClass $class): array
{
    $methods = [];

    foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if (! str_starts_with((string) $method->getFileName(), packageRoot().'/src/')) {
            continue;
        }

        if (str_starts_with($method->getName(), '__') && $method->getName() !== '__construct') {
            continue;
        }

        if (isInternal($method->getDocComment()) || overridesFramework($class, $method->getName())) {
            continue;
        }

        if ($class->isEnum() && in_array($method->getName(), ['cases', 'from', 'tryFrom'], true)) {
            continue;
        }

        $methods[$method->getName()] = $method;
    }

    ksort($methods);

    return array_values($methods);
}

function classSection(string $shortClass): string
{
    $name = PACKAGE_NAMESPACE.$shortClass;
    $class = new ReflectionClass($name);
    $out = '### `'.$class->getShortName()."`\n\n`".$name.'`';

    $summary = docSummary($class->getDocComment());
    $out .= $summary === '' ? "\n\n" : ' — '.$summary."\n\n";

    if ($class->isEnum()) {
        $enum = new ReflectionEnum($name);
        $cases = array_map(function ($case) use ($enum): string {
            $value = $case->getValue();

            return '`'.$case->getName().'`'.($enum->isBacked() && $value instanceof BackedEnum ? ' (`'.$value->value.'`)' : '');
        }, $enum->getCases());

        $out .= 'Cases: '.implode(', ', $cases)."\n\n";
    }

    $methods = array_filter(publicMethods($class), fn (ReflectionMethod $method): bool => ! isGetter($method));
    $getters = array_filter(publicMethods($class), isGetter(...));

    if ($methods !== []) {
        $out .= "| Method | Purpose |\n| --- | --- |\n";
    }

    foreach ($methods as $method) {
        $signature = str_replace('|', '\\|', methodSignature($method));
        $out .= '| `'.$signature.'` | '.docSummary($method->getDocComment())." |\n";
    }

    if ($getters !== []) {
        $out .= ($methods === [] ? '' : "\n").'Getters (read the resolved option; used by the views): '
            .implode(', ', array_map(fn (ReflectionMethod $method): string => '`'.$method->getName().'()`', $getters))."\n";
    }

    return $out."\n";
}

/**
 * Argument-less getters (`getFps()`, `isContinuous()`, …) mirror their
 * setters, so api.md lists them by name only.
 */
function isGetter(ReflectionMethod $method): bool
{
    return ! $method->isStatic()
        && $method->getNumberOfParameters() === 0
        && preg_match('/^(get|is|has|should|allows)[A-Z]/', $method->getName()) === 1;
}

/**
 * @param  array<array-key, mixed>  $config
 * @return list<array{0: string, 1: mixed}>
 */
function configRows(array $config, string $prefix = ''): array
{
    $rows = [];

    foreach ($config as $key => $value) {
        $path = $prefix.$key;

        if (is_array($value) && ! array_is_list($value) && $value !== []) {
            array_push($rows, ...configRows($value, $path.'.'));

            continue;
        }

        $rows[] = [$path, $value];
    }

    return $rows;
}

function configSection(): string
{
    $config = require packageRoot().'/config/qr-code.php';
    $out = "## Config keys\n\nEvery key in `config/qr-code.php` (prefix `qr-code.`). Meanings: [reference.md](reference.md#configuration).\n\n| Key | Default |\n| --- | --- |\n";

    foreach (configRows($config) as [$key, $value]) {
        $out .= '| `'.$key.'` | `'.str_replace('|', '\\|', exportValue($value))."` |\n";
    }

    return $out;
}

function generateApiMarkdown(): string
{
    $out = "# API map\n\n"
        ."> Generated by `composer docs` from `src/` and `config/qr-code.php`. Do not edit by hand.\n\n"
        ."Every public class, method, enum case and config key that is covered by semver. Anything tagged `@internal` is left out on purpose (see [Public API and `@internal`](reference.md#public-api-and-internal)). Methods inherited from Filament (`make()`, `label()`, `statePath()`, …) are not repeated. Guides: [README](../README.md) · [AI guide](ai.md).\n\n"
        ."Artisan commands: `php artisan qr-code:doctor` (environment checks), `php artisan make:qr-scan-field {name}` (scaffolds a `QrScanner` subclass).\n\n";

    foreach (apiSections() as $title => $classes) {
        $out .= '## '.$title."\n\n";

        foreach ($classes as $class) {
            $out .= classSection($class);
        }
    }

    return rtrim($out.configSection())."\n";
}

function generateLlmsFull(): string
{
    $out = "# Filament QR Code — full documentation\n\n"
        ."> Generated by `composer docs`: every user-facing doc of mmuqiitf/filament-qr-code in one file. Source: https://github.com/mmuqiitf/filament-qr-code\n";

    foreach (llmsFullFiles() as $file) {
        $out .= "\n\n---\n\n<!-- file: ".$file." -->\n\n".trim((string) file_get_contents(packageRoot().'/'.$file))."\n";
    }

    return $out;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    require packageRoot().'/vendor/autoload.php';

    file_put_contents(packageRoot().'/docs/api.md', generateApiMarkdown());
    file_put_contents(packageRoot().'/llms-full.txt', generateLlmsFull());

    fwrite(STDOUT, "Wrote docs/api.md and llms-full.txt\n");
}
