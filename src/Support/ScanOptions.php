<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasOutcomeFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Concerns\HasSequentialScan;
use Mmuqiitf\FilamentQrCode\Concerns\InteractsWithScans;
use Throwable;

/**
 * Single source of truth for the option payload passed to the browser.
 * Each scan trait contributes its own keys; the surface adds its extras
 * through getComponentScanPayload().
 */
final class ScanOptions
{
    /** @var array<class-string, string> */
    private const Contributors = [
        HasFeedback::class => 'getFeedbackScanPayload',
        HasOutcomeFeedback::class => 'getOutcomeFeedbackScanPayload',
        HasHardwareScanner::class => 'getHardwareScannerScanPayload',
        HasCameraScanning::class => 'getCameraScanPayload',
        HasSequentialScan::class => 'getSequentialScanPayload',
        InteractsWithScans::class => 'getScanChecksScanPayload',
    ];

    /** @return array<string, mixed> */
    public static function payload(object $component): array
    {
        $traits = class_uses_recursive($component);
        $payload = [];

        foreach (self::Contributors as $trait => $method) {
            if (! in_array($trait, $traits, true)) {
                continue;
            }

            $payload = [...$payload, ...self::contribution($component, $method)];
        }

        $statePath = self::statePathOf($component);

        if ($statePath !== null) {
            $payload['statePath'] = $statePath;
        }

        if (! in_array(HasScanPayload::class, $traits, true)) {
            return $payload;
        }

        return [...$payload, ...self::contribution($component, 'getComponentScanPayload')];
    }

    /** @return array<string, mixed> */
    private static function contribution(object $component, string $method): array
    {
        /** @var callable(): array<string, mixed> $contributor */
        $contributor = [$component, $method];

        return $contributor();
    }

    /**
     * The state path throws when the component is not mounted in a form,
     * so it resolves to null instead.
     */
    private static function statePathOf(object $component): ?string
    {
        if (! method_exists($component, 'getStatePath')) {
            return null;
        }

        try {
            $statePath = $component->getStatePath();
        } catch (Throwable) {
            return null;
        }

        if (! is_string($statePath)) {
            return null;
        }

        if ($statePath === '') {
            return null;
        }

        return $statePath;
    }
}
