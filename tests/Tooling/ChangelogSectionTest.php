<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

function changelogSection(string $version, ?string $file = null): Process
{
    $process = new Process(array_filter(
        ['bash', __DIR__.'/../../.github/scripts/changelog-section.sh', $version, $file],
        fn (?string $argument): bool => $argument !== null,
    ));
    $process->run();

    return $process;
}

it('prints the body of one released version, without its heading or the next version', function () {
    $process = changelogSection('1.0.2');

    expect($process->isSuccessful())->toBeTrue()
        ->and($process->getOutput())
        ->toStartWith('### Added')
        ->toContain('announceUsing')
        ->not->toContain('## [1.0.2]')
        ->not->toContain('## [1.0.1]');
});

it('accepts a tag with a leading v', function () {
    expect(changelogSection('v1.0.2')->getOutput())->toBe(changelogSection('1.0.2')->getOutput());
});

it('stops at the link reference definitions after the last version', function () {
    $file = tempnam(sys_get_temp_dir(), 'changelog');
    file_put_contents($file, "# Changelog\n\n## [0.1.0] - 2026-01-01\n\n### Added\n\n- First.\n\n[0.1.0]: https://example.com\n");

    expect(trim(changelogSection('0.1.0', $file)->getOutput()))->toBe("### Added\n\n- First.");

    unlink($file);
});

it('fails for a version with no section, so no empty release is published', function () {
    $process = changelogSection('9.9.9');

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getErrorOutput())->toContain('9.9.9');
});
