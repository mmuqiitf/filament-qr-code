<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

/**
 * The translated strings the browser shows or announces, keyed by their
 * scan-payload option name. resources/js/messages.js holds the en fallbacks
 * for components mounted without a payload.
 *
 * @internal
 */
final class ScanMessages
{
    private const Camera = [
        'insecureMessage' => 'filament-qr-code::ui.camera_needs_secure_context',
        'fileScanFailedMessage' => 'filament-qr-code::ui.file_scan_failed',
        'decoderFailedMessage' => 'filament-qr-code::ui.camera_decoder_failed',
        'cameraUnavailableMessage' => 'filament-qr-code::ui.camera_unavailable',
        'noCamerasMessage' => 'filament-qr-code::ui.camera_none_found',
        'cameraStartFailedMessage' => 'filament-qr-code::ui.camera_start_failed',
    ];

    private const Announcements = [
        'duplicateMessage' => 'filament-qr-code::ui.duplicate_codes',
        'addedAnnouncement' => 'filament-qr-code::ui.announce_added',
        'stepAddedAnnouncement' => 'filament-qr-code::ui.announce_step_added',
        'completedAnnouncement' => 'filament-qr-code::ui.announce_completed',
        'rejectedAnnouncement' => 'filament-qr-code::ui.announce_rejected',
    ];

    private const Checks = [
        'scanFailedMessage' => 'filament-qr-code::ui.scan_failed',
    ];

    /** @return array<string, string> */
    public static function forCamera(): array
    {
        return self::translate(self::Camera);
    }

    /** @return array<string, string> */
    public static function forAnnouncements(): array
    {
        return self::translate(self::Announcements);
    }

    public static function scanFailed(): string
    {
        return self::translate(self::Checks)['scanFailedMessage'];
    }

    /**
     * Option name => ui key (without the namespace) of every message.
     *
     * @return array<string, string>
     */
    public static function keys(): array
    {
        return array_map(
            fn (string $key): string => substr($key, strlen('filament-qr-code::ui.')),
            [...self::Camera, ...self::Announcements, ...self::Checks],
        );
    }

    /**
     * @param  array<string, string>  $keys
     * @return array<string, string>
     */
    private static function translate(array $keys): array
    {
        return array_map(fn (string $key): string => (string) __($key), $keys);
    }
}
