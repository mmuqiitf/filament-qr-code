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
    public const SCANNER_START = 'filament-qr-code::scanner.start';

    public const SCANNER_END = 'filament-qr-code::scanner.end';

    public const SCANNER_MODAL_START = 'filament-qr-code::scanner.modal.start';

    public const SCANNER_MODAL_END = 'filament-qr-code::scanner.modal.end';

    public const SEQUENCE_START = 'filament-qr-code::sequence.start';

    public const SEQUENCE_END = 'filament-qr-code::sequence.end';

    public const SEQUENCE_STEPS_BEFORE = 'filament-qr-code::sequence.steps.before';

    public const SEQUENCE_STEPS_AFTER = 'filament-qr-code::sequence.steps.after';

    public const SEQUENCE_STEP_HEADER = 'filament-qr-code::sequence.step.header';

    public const COLLECTOR_START = 'filament-qr-code::collector.start';

    public const COLLECTOR_END = 'filament-qr-code::collector.end';
}
