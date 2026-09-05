<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Hardware Wedge Scanner Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for physical USB/Bluetooth handheld barcode and QR scanners.
    |
    | - burst_threshold_ms: Max milliseconds between keystrokes to identify a burst.
    | - default_terminators: Suffix keys that finish a scan (Enter, Tab).
    | - prevent_form_submit: Whether to swallow Enter events during scanner bursts.
    |
    */
    'hardware_scanner' => [
        'enabled' => true,
        'burst_threshold_ms' => 50,
        'min_barcode_length' => 2,
        'prevent_form_submit' => true,
        'default_terminators' => ['Enter', 'Tab'],
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
        'fps' => 15,
        'qrbox' => 250,
        'prefer_rear_camera' => true,
        'show_torch_button' => true,
        'show_zoom_slider' => true,
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
    ],
];
