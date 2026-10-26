<?php

declare(strict_types=1);

use PhpParser\Error;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

function qrDocsPath(string $path): string
{
    return dirname(__DIR__, 2).'/'.$path;
}

function qrSnippetFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [$root.'/README.md', $root.'/UPGRADE.md'];

    foreach (Finder::create()->files()->in($root.'/docs')->name('*.md') as $file) {
        $files[] = $file->getRealPath();
    }

    return $files;
}

function qrPhpBlocks(): array
{
    $blocks = [];

    foreach (qrSnippetFiles() as $file) {
        // Fences may be indented inside list items; strip that indent from the code.
        preg_match_all('/^( *)```php\n(.*?)^\1```/ms', file_get_contents($file), $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            [$indent, [$code, $offset]] = [$match[1][0], $match[2]];
            $line = substr_count(file_get_contents($file), "\n", 0, $offset) + 1;
            $blocks[substr($file, strlen(dirname(__DIR__, 2)) + 1).':'.$line] = (string) preg_replace('/^'.$indent.'/m', '', $code);
        }
    }

    return $blocks;
}

/**
 * Snippets are often fragments: statements, config entries, builder chains
 * or class members, with `[...]` / `...` placeholders. Each blank-line
 * separated chunk must parse in one of the usual wrappings.
 */
function qrParseChunk(string $chunk): bool
{
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    $statements = preg_replace('/\)\n(?=[A-Za-z$\\\\])/', ");\n", $chunk);

    $candidates = [
        "<?php\n{$chunk}",
        "<?php\n{$chunk};",
        "<?php\n{$statements};",
        "<?php\n\$x{$chunk};",
        "<?php\nclass __Snippet {\n{$chunk}\n}",
        "<?php\n\$x = [\n{$chunk}\n];",
    ];

    foreach ($candidates as $candidate) {
        try {
            $parser->parse($candidate);

            return true;
        } catch (Error) {
        }
    }

    return false;
}

function qrParseSnippet(string $code): bool
{
    $body = preg_replace('/^\s*<\?php\s*/', '', $code);

    if (qrParseChunk($body)) {
        return true;
    }

    $body = str_replace(['[...]', '/* ... */', '// ...', '(...)'], ['[]', 'null', '', '()'], $body);
    $body = preg_replace(['/=> \.\.\./', '/^\s*\.\.\.,?\s*$/m', '#/\* [^*\n]*\*/#'], ['=> null', '', 'null'], $body);

    if (qrParseChunk($body) || qrParseChunk(rtrim(trim($body), ','))) {
        return true;
    }

    foreach (preg_split('/\n\s*\n(?=\S)/', $body) as $chunk) {
        $chunk = rtrim(trim($chunk), ',');

        if ($chunk !== '' && ! qrParseChunk($chunk)) {
            return false;
        }
    }

    return true;
}
