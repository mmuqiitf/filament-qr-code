<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

/**
 * Exact default key sets, not full payload snapshots. A new default key is a
 * contract change; opt-in options must stay off this list until they are
 * configured (see ScanOptionsPayloadTest for values).
 *
 * @return list<string>
 */
function defaultScanPayloadKeys(string $surface): array
{
    $feedback = ['beepDurationMs', 'beepFrequency', 'burstThresholdMs', 'minBarcodeLength', 'minFastKeys', 'preventSubmit', 'scanTimeoutMs', 'sound', 'terminators', 'vibrate', 'vibrateDurationMs'];
    $camera = ['cameraControls', 'cameraStartFailedMessage', 'cameraUnavailableMessage', 'decoderFailedMessage', 'fileScanFailedMessage', 'formats', 'fps', 'hardwareScanner', 'insecureMessage', 'noCamerasMessage', 'offline', 'offlineBlockedMessage', 'preferRearCamera', 'qrbox', 'rejectedAnnouncement'];

    return match ($surface) {
        'scanner' => [...$feedback, ...$camera, 'announcements', 'clearOnReject', 'continuousDisplay', 'cooldownMs', 'customAnnouncements', 'errorFeedback', 'fieldAddedAnnouncement', 'fieldLabel', 'gs1', 'hardwareScope', 'keepCameraOpen', 'mode', 'nextField', 'pauseOnReject', 'scanFailedMessage', 'scanRejectedMessage', 'serverCheck', 'successFeedback', 'syncOptions'],
        'sequence' => [...$feedback, ...$camera, 'addedAnnouncement', 'announcements', 'completedAnnouncement', 'componentStatePath', 'customAnnouncements', 'duplicateMessage', 'editable', 'fields', 'scanFailedMessage', 'serverCheck', 'statePrefix', 'stepAddedAnnouncement', 'steps', 'syncOptions', 'values'],
        'collector' => [...$feedback, ...$camera, 'addedAnnouncement', 'allowDuplicates', 'announcements', 'completedAnnouncement', 'customAnnouncements', 'delayBetweenScansMs', 'duplicateMessage', 'hasItemRules', 'stepAddedAnnouncement', 'wireScanHandler'],
        'listener' => [...$feedback, 'autoFocusNext', 'fallbackToAnyInput', 'fields', 'gs1', 'hardwareScanner'],
        'collect-action' => [...$feedback, ...$camera, 'addedAnnouncement', 'allowDuplicates', 'completedAnnouncement', 'duplicateMessage', 'stepAddedAnnouncement', 'wireScanHandler'],
        default => throw new InvalidArgumentException("Unknown scan surface [{$surface}]."),
    };
}

dataset('default scan surfaces', [
    'scanner' => [fn () => QrScanner::make('sku'), defaultScanPayloadKeys('scanner')],
    'sequence' => [fn () => QrScanSequence::make(['step', 'employee']), defaultScanPayloadKeys('sequence')],
    'collector' => [fn () => QrCollector::make('codes'), defaultScanPayloadKeys('collector')],
    'listener' => [fn () => QrHardwareScannerListener::make(['sku']), defaultScanPayloadKeys('listener')],
    'collect action' => [fn () => QrCollectAction::make(), defaultScanPayloadKeys('collect-action')],
]);

it('keeps the default scan payload key set stable', function (object $component, array $keys) {
    expect(array_keys($component->getScanPayload()))->toEqualCanonicalizing($keys);
})->with('default scan surfaces');
