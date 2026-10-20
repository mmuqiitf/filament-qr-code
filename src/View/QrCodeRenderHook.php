<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\View;

/**
 * Render hooks inside the scan views. Register content with
 * FilamentView::registerRenderHook(QrCodeRenderHook::SCANNER_START, fn () => ...),
 * optionally scoped to a Livewire class or a field state path (e.g. 'data.sku').
 *
 * Hooks inside a sequence step render once into the Alpine step template,
 * so their content repeats on every step and should be static markup.
 */
final class QrCodeRenderHook
{
    public const string SCANNER_START = 'filament-qr-code::scanner.start';

    public const string SCANNER_END = 'filament-qr-code::scanner.end';

    public const string SCANNER_MODAL_START = 'filament-qr-code::scanner.modal.start';

    public const string SCANNER_MODAL_END = 'filament-qr-code::scanner.modal.end';

    public const string SEQUENCE_START = 'filament-qr-code::sequence.start';

    public const string SEQUENCE_END = 'filament-qr-code::sequence.end';

    public const string SEQUENCE_STEPS_BEFORE = 'filament-qr-code::sequence.steps.before';

    public const string SEQUENCE_STEPS_AFTER = 'filament-qr-code::sequence.steps.after';

    public const string SEQUENCE_STEP_HEADER = 'filament-qr-code::sequence.step.header';

    public const string COLLECTOR_START = 'filament-qr-code::collector.start';

    public const string COLLECTOR_END = 'filament-qr-code::collector.end';
}
