<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Closure;
use Throwable;

/**
 * Single source of truth for the option payload passed to the browser.
 *
 * Five Blade views used to repeat the same ~15 option keys by hand, which
 * meant the `qr-code.camera`, `qr-code.hardware_scanner`, and
 * `qr-code.feedback.sound` config values never reached the browser, and the
 * collect-action modal drifted (`$beepDuration` vs `$beepDurationMs`).
 * Each scan surface now exposes one `getScanPayload()` method backed by this
 * Module, so key names and defaults live in exactly one place.
 */
final class ScanOptions
{
    /**
     * Build the Alpine init payload for any scan surface.
     *
     * Only keys the component supports (via its getters) are included;
     * unknown keys are simply omitted and the JS component defaults apply.
     * Extra keys are harmless: every Alpine scan Module destructures only
     * the params it needs.
     *
     * @return array<string, mixed>
     */
    public static function payload(object $component): array
    {
        $payload = [];

        // Feedback (HasFeedback).
        if (method_exists($component, 'hasSound')) {
            $payload['sound'] = $component->hasSound();
        }

        if (method_exists($component, 'hasVibration')) {
            $payload['vibrate'] = $component->hasVibration();
        }

        if (method_exists($component, 'getBeepFrequencyHz')) {
            $payload['beepFrequency'] = $component->getBeepFrequencyHz();
        }

        if (method_exists($component, 'getBeepDurationMs')) {
            $payload['beepDurationMs'] = $component->getBeepDurationMs();
        }

        if (method_exists($component, 'getVibrateDurationMs')) {
            $payload['vibrateDurationMs'] = $component->getVibrateDurationMs();
        }

        // Hardware scanner (HasHardwareScanner).
        if (method_exists($component, 'isHardwareScannerEnabled')) {
            $payload['hardwareScanner'] = $component->isHardwareScannerEnabled();
        }

        if (method_exists($component, 'getBurstThresholdMs')) {
            $payload['burstThresholdMs'] = $component->getBurstThresholdMs();
        }

        if (method_exists($component, 'getTerminators')) {
            $payload['terminators'] = $component->getTerminators();
        }

        if (method_exists($component, 'getMinBarcodeLength')) {
            $payload['minBarcodeLength'] = $component->getMinBarcodeLength();
        }

        if (method_exists($component, 'getScanTimeoutMs')) {
            $payload['scanTimeoutMs'] = $component->getScanTimeoutMs();
        }

        if (method_exists($component, 'isSuppressedWhenGlobalListenerActive')) {
            $payload['suppressWhenGlobalListenerActive'] = $component->isSuppressedWhenGlobalListenerActive();
        }

        if (method_exists($component, 'shouldPreventFormSubmit')) {
            $payload['preventSubmit'] = $component->shouldPreventFormSubmit();
        }

        // Camera (HasCameraScanning).
        $hasCamera = false;

        if (method_exists($component, 'getEffectiveFps')) {
            $payload['fps'] = $component->getEffectiveFps();
            $hasCamera = true;
        }

        if (method_exists($component, 'getQrbox')) {
            $payload['qrbox'] = $component->getQrbox();
            $hasCamera = true;
        }

        if (method_exists($component, 'isPreferRearCamera')) {
            $payload['preferRearCamera'] = $component->isPreferRearCamera();
            $hasCamera = true;
        }

        if (method_exists($component, 'getSupportedFormats')) {
            $payload['formats'] = $component->getSupportedFormats();
            $hasCamera = true;
        }

        if ($hasCamera) {
            $payload['insecureMessage'] = __('filament-qr-code::ui.camera_needs_secure_context');
        }

        // Surface-specific extras. The state path can throw when the component
        // is not mounted in a form, so it resolves to null instead.
        if (method_exists($component, 'getStatePath')) {
            $statePath = self::attempt(fn () => $component->getStatePath());
            if (is_string($statePath) && $statePath !== '') {
                $payload['statePath'] = $statePath;
            }
        }

        if (method_exists($component, 'getNextField')) {
            $payload['nextField'] = $component->getNextField();
        }

        if (method_exists($component, 'getScanFields')) {
            $payload['fields'] = $component->getScanFields();
        } elseif (method_exists($component, 'getFields')) {
            $payload['fields'] = $component->getFields();
        }

        if (method_exists($component, 'getStatePathPrefix')) {
            $payload['statePrefix'] = $component->getStatePathPrefix();
        }

        if (method_exists($component, 'getComponentStatePath')) {
            $payload['componentStatePath'] = $component->getComponentStatePath();
        }

        if (method_exists($component, 'isEditable')) {
            $payload['editable'] = $component->isEditable();
        }

        if (method_exists($component, 'isDuplicatesAllowed')) {
            $payload['allowDuplicates'] = $component->isDuplicatesAllowed();
        }

        if (method_exists($component, 'getDelayBetweenScansMs')) {
            $payload['delayBetweenScansMs'] = $component->getDelayBetweenScansMs();
        }

        if (method_exists($component, 'isAutoFocusNext')) {
            $payload['autoFocusNext'] = $component->isAutoFocusNext();
        }

        return $payload;
    }

    private static function attempt(Closure $call): mixed
    {
        try {
            return $call();
        } catch (Throwable) {
            return null;
        }
    }
}
