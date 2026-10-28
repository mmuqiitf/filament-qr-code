<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Hardware Scanner Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for physical USB/Bluetooth handheld barcode and QR scanners.
    |
    | - burst_threshold_ms: Max milliseconds between keystrokes to identify a burst.
    | - default_terminators: Suffix keys that finish a scan (Enter, Tab).
    | - prevent_form_submit: Whether to swallow Enter events during scanner bursts.
    | - min_fast_keys: Consecutive fast keystroke gaps required before a
    |   terminator counts as a scan. 3 (the 1.0 default) rejects fast human
    |   typing; 1 restores the 0.x behaviour.
    | - fallback_to_any_input: When the page-global listener finds no focused
    |   or registered empty field, write into the first empty form input
    |   (v0.2 behaviour). Set false to dispatch `qr-scan-unrouted` instead.
    | - default_scope: Where a QrScanner field catches wedge scans. 'focus'
    |   (only while focused) or 'page' (also when no scan field has focus).
    |   Override per field with hardwareInput(scope:).
    |
    */
    'hardware_scanner' => [
        'enabled' => true,
        'burst_threshold_ms' => 50,
        'min_barcode_length' => 2,
        'min_fast_keys' => 3,
        'fallback_to_any_input' => true,
        'scan_timeout_ms' => 150,
        'prevent_form_submit' => true,
        'default_terminators' => ['Enter', 'Tab'],
        'default_scope' => 'focus',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensory Feedback
    |--------------------------------------------------------------------------
    |
    | Sound and haptic confirmation settings upon successful scan decoding.
    |
    */
    'feedback' => [
        'sound' => true,
        'beep_frequency' => 880, // Hz (A5 pitch)
        'beep_duration_ms' => 80,
        'vibrate' => true,
        'vibrate_duration_ms' => 100,

        // Outcome feedback for fields with server checks (validateUsing, lookup,
        // onScanned, onRejected). sound / vibrate above stay master switches.
        // Override per field with beep(), vibrate(success:, error:) and flash().
        'success' => [
            'sound' => true,
            'vibrate' => [100], // ms pattern
            'flash' => true,
        ],
        'error' => [
            'sound' => true,
            'vibrate' => [80, 60, 80],
            'flash' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Live Scan Checks
    |--------------------------------------------------------------------------
    |
    | - cooldown_ms: Same code scanned again within this window is ignored.
    |   Used only by fields with server checks (validateUsing / lookup);
    |   fields without checks default to 0. Override per field with cooldown().
    | - clear_on_reject: Clear the field when a live scan is rejected.
    |   Override per field with clearOnReject().
    | - continuous: Defaults for QrScanner::mode(ScanMode::Continuous).
    |   display: 'empty' clears the field after each accepted scan,
    |   'last_code' keeps the code (continuousDisplay()). pause_on_reject:
    |   false shows the error and keeps scanning, true pauses until dismissed
    |   (pauseOnReject()). keep_camera_open: false closes the camera after
    |   each scan (keepCameraOpen()). cooldown_ms: duplicate window for
    |   continuous fields (cooldown() overrides).
    |
    */
    'scan' => [
        // When the browser picks up options changed on the server: 'morph'
        // applies them on every Livewire re-render, 'refresh' only on a
        // qr-scanner-refresh / qr-sequence-refresh event. Override per field
        // with syncOptions().
        'sync_options' => 'morph',
        'cooldown_ms' => 1500,
        'clear_on_reject' => true,
        'continuous' => [
            'display' => 'empty',
            'pause_on_reject' => false,
            'keep_camera_open' => false,
            'cooldown_ms' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Sequence Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults for QrScanSequence steps. on_invalid: what a failed
    | validateAllUsing() check does: 'jump_to_step' keeps the values and asks
    | for the failing step again, 'restart' clears every step
    | (onInvalidSequence()). auto_submit: call the Livewire submit_method
    | once the sequence completes (autoSubmit()). reset_after_submit: clear
    | the steps after a successful submit (resetAfterSubmit()). continuous:
    | start over after every completed sequence (continuous()).
    | repeat_complete: what a full repeat(n) step does, 'advance' to the
    | next step or 'wait' for a finish scan or the Done control
    | (onRepeatComplete()). finish_code: the scan that finishes a waiting or
    | open-ended repeat step (finishWith() overrides it per step).
    |
    */
    'sequence' => [
        'on_invalid' => 'jump_to_step',
        'auto_submit' => false,
        'submit_method' => 'submit',
        'reset_after_submit' => false,
        'continuous' => false,
        'repeat_complete' => 'advance',
        'finish_code' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Camera Scanner Defaults
    |--------------------------------------------------------------------------
    |
    | Default configuration for the HTML5-QRCode scanner viewfinder.
    |
    */
    'camera' => [
        'fps' => 25,
        'qrbox' => 250,
        'prefer_rear_camera' => true,
        // Torch / zoom buttons, shown only when the camera supports them.
        'controls' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Generator Defaults
    |--------------------------------------------------------------------------
    |
    | Default parameters for generated QR codes.
    |
    */
    'generator' => [
        'size' => 300,
        'margin' => 2,
        'format' => 'svg', // 'svg' or 'png'
        'foreground_color' => '#000000',
        'background_color' => '#ffffff',
        'error_correction' => 'M', // L, M, Q, H
        'cache_ttl' => 86400, // seconds; null = forever, false = disable persistent L2
        'cache_store' => null, // null = default cache store
        // Directories logo files must live in (realpath-checked, symlinks
        // resolved). null = allow any readable path (1.0.0 behaviour).
        'logo_base_paths' => null, // e.g. [storage_path('app/qr-logos')]
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Route Hardening
    |--------------------------------------------------------------------------
    |
    | Opt-in limits for the signed filamentQrCode.image route that serves
    | lazy modal previews. Everything is off by default.
    |
    */
    'image_route' => [
        'ttl' => null, // minutes; null = signed URLs never expire
        'throttle' => null, // e.g. '60,1' = 60 requests per minute per client
        'strict_colors' => false, // true = colours must be #rrggbb, else 422
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Audit
    |--------------------------------------------------------------------------
    |
    | Every server-observed scan (live scans accepted by a QrScanner with
    | server checks, collector action handling, programmatic triggers)
    | dispatches a QrCodeScanned event. Enable the log listener to record
    | them. Rejected scans fire ScanRejected and are not logged here.
    |
    */
    'audit' => [
        'enabled' => false,
        'channel' => null, // null = default log stack
    ],
];
