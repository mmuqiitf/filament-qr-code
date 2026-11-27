<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Unit;

use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Enums\RepeatComplete;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Support\Gs1ScanCheck;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use Mmuqiitf\FilamentQrCode\Support\SequenceEngine;
use Mmuqiitf\FilamentQrCode\Support\StepEvaluator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SequenceEngineTest extends TestCase
{
    public function test_steps_validate_normalize_and_complete_in_order(): void
    {
        $written = [];
        $accepted = [];
        $completed = null;

        $engine = new SequenceEngine(
            steps: [
                ScanStep::make('po')
                    ->normalizeUsing(fn (string $code): string => strtoupper(trim($code)))
                    ->validateUsing(fn (string $code): bool|string => str_starts_with($code, 'PO-') ?: 'Must start with PO-'),
                ScanStep::make('bin')
                    ->validateUsing(fn (string $code, array $values): bool|string => ($values['po'] ?? null) !== null && str_starts_with($code, 'BIN-') ?: 'Invalid bin'),
            ],
            onWriteValues: function (array $values) use (&$written): void {
                $written = $values;
            },
            onStepAccepted: function (ScanResult $result, string $step, array $values) use (&$accepted): void {
                $accepted[] = [$step, $result->code, $values];
            },
            onSequenceCompleted: function (array $values) use (&$completed): ?bool {
                $completed = $values;

                return null;
            },
        );

        $bad = $engine->handleScan('po', 'bad', ScanInput::Camera, []);
        $this->assertFalse($bad->scan->passes);
        $this->assertSame('Must start with PO-', $bad->scan->message);
        $this->assertSame('po', $bad->nextStep);
        $this->assertSame([], $written);

        $first = $engine->handleScan('po', ' po-101 ', ScanInput::Camera, []);
        $this->assertTrue($first->scan->passes);
        $this->assertSame('PO-101', $first->scan->code);
        $this->assertSame('bin', $first->nextStep);
        $this->assertFalse($first->completed);

        $second = $engine->handleScan('bin', 'BIN-9', ScanInput::Hardware, $first->values);
        $this->assertTrue($second->scan->passes);
        $this->assertTrue($second->completed);
        $this->assertNull($second->nextStep);
        $this->assertSame(['po' => 'PO-101', 'bin' => 'BIN-9'], $completed);
        $this->assertSame(['po' => 'PO-101', 'bin' => 'BIN-9'], $written);
        $this->assertCount(2, $accepted);
    }

    public function test_optional_steps_skip_on_hardware_or_camera_but_not_manual_and_can_land_on_repeat(): void
    {
        $engine = new SequenceEngine(
            steps: [
                ScanStep::make('pallet')->optional()->validateUsing(fn (string $code): bool => str_starts_with($code, 'PAL-')),
                ScanStep::make('note')->optional()->validateUsing(fn (string $code): bool => str_starts_with($code, 'NOTE-')),
                ScanStep::make('items')->repeat(2)->validateUsing(fn (string $code): bool => str_starts_with($code, 'SKU-')),
            ],
        );

        $manual = $engine->handleScan('pallet', 'SKU-1', ScanInput::Manual, []);
        $this->assertFalse($manual->scan->passes);
        $this->assertSame('pallet', $manual->nextStep);

        $skipped = $engine->handleScan('pallet', 'SKU-1', ScanInput::Camera, ['pallet' => 'stale']);
        $this->assertTrue($skipped->scan->passes);
        $this->assertSame(['pallet', 'note'], $skipped->skipped);
        $this->assertSame('items', $skipped->step);
        $this->assertSame('items', $skipped->nextStep);
        $this->assertSame(['items' => ['SKU-1']], $skipped->values);
    }

    public function test_repeat_counting_duplicates_finish_code_and_wait_behavior(): void
    {
        $warnings = [];
        $evaluator = new StepEvaluator(
            onWarning: function (string $message) use (&$warnings): void {
                $warnings[] = $message;
            },
        );

        $engine = new SequenceEngine(
            steps: [
                ScanStep::make('qty_hint')->optional(),
                ScanStep::make('serials')
                    ->repeat(function (array $values): int {
                        if (! isset($values['qty_hint'])) {
                            throw new RuntimeException('qty_hint missing');
                        }

                        return (int) $values['qty_hint'];
                    })
                    ->onRepeatComplete(RepeatComplete::Wait)
                    ->finishWith('DONE'),
            ],
            evaluator: $evaluator,
        );

        // Broken repeat() closure falls back to open-ended and emits a warning.
        $one = $engine->handleScan('serials', 'SN-1', ScanInput::Hardware, []);
        $this->assertTrue($one->scan->passes);
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('repeat()', $warnings[0]);

        // Duplicate rejected by default.
        $dup = $engine->handleScan('serials', 'SN-1', ScanInput::Hardware, $one->values);
        $this->assertFalse($dup->scan->passes);

        // Counted repeat with Wait stays on step when full until DONE / finishStep.
        $seeded = ['qty_hint' => '2', 'serials' => ['SN-1']];
        $full = $engine->handleScan('serials', 'SN-2', ScanInput::Hardware, $seeded);
        $this->assertTrue($full->scan->passes);
        $this->assertSame('serials', $full->nextStep);
        $this->assertFalse($full->completed);

        $finished = $engine->handleScan('serials', 'DONE', ScanInput::Hardware, $full->values);
        $this->assertTrue($finished->finished);
        $this->assertTrue($finished->completed);
        $this->assertSame(['qty_hint' => '2', 'serials' => ['SN-1', 'SN-2']], $finished->values);

        // Removing a code shrinks the repeat array cleanly.
        $removed = $engine->removeStepCode('serials', 0, $full->values);
        $this->assertSame('SN-1', $removed->scan->code);
        $this->assertSame(['qty_hint' => '2', 'serials' => ['SN-2']], $removed->values);
    }

    public function test_when_conditions_filter_inactive_steps_and_broken_conditions_stay_active(): void
    {
        $warnings = [];
        $evaluator = new StepEvaluator(
            onWarning: function (string $message) use (&$warnings): void {
                $warnings[] = $message;
            },
        );

        $engine = new SequenceEngine(
            steps: [
                ScanStep::make('mode'),
                ScanStep::make('lot')->when(fn (array $values): bool => ($values['mode'] ?? '') === 'LOT'),
                ScanStep::make('broken')->when(function (): bool {
                    throw new RuntimeException('boom');
                }),
            ],
            evaluator: $evaluator,
        );

        $step1 = $engine->handleScan('mode', 'PLAIN', ScanInput::Camera, ['lot' => 'OLD-LOT']);
        $this->assertSame('broken', $step1->nextStep);
        $this->assertArrayNotHasKey('lot', $step1->values);
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('when()', $warnings[0]);
    }

    public function test_gs1_bridging_validates_normalizes_to_bracketed_and_emits_fill_plan(): void
    {
        $filled = null;

        $engine = new SequenceEngine(
            steps: [
                ScanStep::make('label')->gs1(
                    fill: ['01' => 'gtin', '10' => 'batch'],
                    require: ['01', '10'],
                    keepMissing: true,
                ),
            ],
            onFillGs1: function (ElementString $elements, Gs1ScanCheck $check, bool $keepMissing) use (&$filled): void {
                $filled = [
                    'gtin' => $elements->get('01'),
                    'batch' => $elements->get('10'),
                    'targets' => $check->fill,
                    'keepMissing' => $keepMissing,
                ];
            },
        );

        $plain = $engine->handleScan('label', '4006381333931', ScanInput::Camera, []);
        $this->assertFalse($plain->scan->passes);

        $missing = $engine->handleScan('label', '(01)09506000134352', ScanInput::Camera, []);
        $this->assertFalse($missing->scan->passes);

        $raw = "]C1010950600013435210LOT7\x1d17261231";
        $ok = $engine->handleScan('label', $raw, ScanInput::Hardware, []);
        $this->assertTrue($ok->scan->passes);
        $this->assertSame('(01)09506000134352(10)LOT7(17)261231', $ok->scan->code);
        $this->assertSame([
            'gtin' => '09506000134352',
            'batch' => 'LOT7',
            'targets' => ['01' => 'gtin', '10' => 'batch'],
            'keepMissing' => true,
        ], $filled);
    }

    public function test_invalid_whole_set_jump_and_restart_with_sticky_and_state_merging(): void
    {
        $jumpEngine = new SequenceEngine(
            steps: [
                ScanStep::make('wh')->sticky(),
                ScanStep::make('items')->repeat(2),
            ],
            invalidBehavior: InvalidSequenceBehavior::JumpToStep,
            validateAllUsing: fn (array $values): array => ['items' => 'Pair mismatch'],
        );

        $jumped = $jumpEngine->handleScan('items', 'B', ScanInput::Camera, ['wh' => 'WH-1', 'items' => ['A']]);
        $this->assertFalse($jumped->scan->passes);
        $this->assertSame('items', $jumped->invalidStep);
        $this->assertSame('items', $jumped->nextStep);
        $this->assertSame(['wh' => 'WH-1', 'items' => ['A']], $jumped->values);

        $restartEngine = new SequenceEngine(
            steps: [
                ScanStep::make('wh')->sticky(),
                ScanStep::make('sku'),
            ],
            invalidBehavior: InvalidSequenceBehavior::Restart,
            validateAllUsing: fn (): string => 'Rejected set',
        );

        $restarted = $restartEngine->handleScan('sku', 'SKU-1', ScanInput::Camera, ['wh' => 'WH-1']);
        $this->assertFalse($restarted->scan->passes);
        $this->assertTrue($restarted->reset);
        $this->assertSame('sku', $restarted->nextStep);
        $this->assertSame(['wh' => 'WH-1'], $restarted->values);

        // State merging preserves existing non-blank form fields and fills missing ones.
        $merged = $restartEngine->mergeState(
            ['wh' => 'FORM-WH', 'sku' => ''],
            ['wh' => 'SEQ-WH', 'sku' => 'SKU-2'],
        );
        $this->assertSame(['wh' => 'FORM-WH', 'sku' => 'SKU-2'], $merged);
        $this->assertSame([], $restartEngine->missingKeys($merged));
        $this->assertSame(['sku'], $restartEngine->missingKeys(['wh' => 'FORM-WH']));
    }

    public function test_continuous_reset_keeps_sticky_and_values_before_step_slices_active_history(): void
    {
        $evaluator = new StepEvaluator;
        $steps = [
            ScanStep::make('wh')->sticky(),
            ScanStep::make('hidden')->when(fn (): bool => false),
            ScanStep::make('items')->repeat(3),
            ScanStep::make('bin'),
        ];

        $engine = new SequenceEngine(
            steps: $steps,
            evaluator: $evaluator,
            isContinuous: true,
        );

        $before = $evaluator->valuesBeforeStep(
            $steps,
            $steps[2],
            ['wh' => 'WH-1', 'hidden' => 'STALE', 'items' => ['A', 'B', 'C'], 'bin' => 'BIN-1'],
            position: 2,
        );
        $this->assertSame(['wh' => 'WH-1', 'items' => ['A', 'B']], $before);

        $completed = $engine->handleScan('bin', 'BIN-9', ScanInput::Camera, ['wh' => 'WH-1', 'items' => ['A', 'B', 'C']]);
        $this->assertTrue($completed->completed);
        $this->assertTrue($completed->reset);
        $this->assertSame('items', $completed->nextStep);
        $this->assertSame(['wh' => 'WH-1'], $completed->values);

        $cleared = $engine->clearStep('wh', $completed->values);
        $this->assertTrue($cleared->scan->passes);
        $this->assertSame([], $cleared->values);
        $this->assertSame(['wh' => 'WH-2'], $engine->resetValues($completed->values, keepSticky: false, seedValues: ['wh' => 'WH-2']));
    }

    public function test_finish_step_handles_optional_empty_undercount_overlimit_and_unknown_steps(): void
    {
        $engine = new SequenceEngine(
            steps: [
                ScanStep::make('opt')->optional()->repeat(),
                ScanStep::make('req')->repeat(2),
            ],
        );

        $unknown = $engine->handleScan('missing', 'X', ScanInput::Camera, []);
        $this->assertFalse($unknown->scan->passes);
        $this->assertNull($unknown->step);

        $optFinished = $engine->finishStep('opt', []);
        $this->assertTrue($optFinished->finished);
        $this->assertSame('req', $optFinished->nextStep);

        $under = $engine->finishStep('req', ['req' => ['ONE']]);
        $this->assertFalse($under->scan->passes);
        $this->assertSame('req', $under->nextStep);

        $over = $engine->finishStep('req', ['req' => ['1', '2', '3']]);
        $this->assertFalse($over->scan->passes);
        $this->assertSame('req', $over->nextStep);

        $badRemove = $engine->removeStepCode('missing', 0, []);
        $this->assertFalse($badRemove->scan->passes);
        $this->assertNull($badRemove->step);
    }
}
