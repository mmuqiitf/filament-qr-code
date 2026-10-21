<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:qr-scan-field')]
class MakeQrScanFieldCommand extends GeneratorCommand
{
    /** @var string */
    protected $name = 'make:qr-scan-field';

    /** @var string */
    protected $description = 'Create a reusable QrScanner (or QrScanSequence with --sequence) field class';

    /** @var string */
    protected $type = 'Scan field';

    protected function getStub(): string
    {
        $stub = $this->option('sequence') ? 'qr-scan-sequence-field.stub' : 'qr-scan-field.stub';
        $published = $this->laravel->basePath('stubs/filament-qr-code/'.$stub);

        return file_exists($published) ? $published : __DIR__.'/../../../stubs/'.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Filament\\Forms\\Components';
    }

    /** @return array<int, array<int, mixed>> */
    protected function getOptions(): array
    {
        return [
            ['sequence', 's', InputOption::VALUE_NONE, 'Extend QrScanSequence instead of QrScanner'],
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite the class if it already exists'],
        ];
    }
}
