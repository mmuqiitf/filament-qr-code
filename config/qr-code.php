<?php

// config/qr-code.php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Camera Facing
    |--------------------------------------------------------------------------
    |
    | The default camera direction to use when scanning QR codes.
    | Options: 'user' (front), 'environment' (back), 'auto'
    |
    */
    'camera_facing' => 'environment',

    /*
    |--------------------------------------------------------------------------
    | Default Scan Mode
    |--------------------------------------------------------------------------
    |
    | The default scan behavior after a successful scan.
    | Options: 'single', 'continuous', 'sequence'
    |
    */
    'scan_mode' => 'single',

    /*
    |--------------------------------------------------------------------------
    | Scan Delay (milliseconds)
    |--------------------------------------------------------------------------
    |
    | The debounce delay between scans to prevent duplicate reads.
    |
    */
    'scan_delay' => 1500,

    /*
    |--------------------------------------------------------------------------
    | Frames Per Second
    |--------------------------------------------------------------------------
    |
    | The FPS for the camera feed. Higher values are smoother but use more CPU.
    |
    */
    'fps' => 30,

    /*
    |--------------------------------------------------------------------------
    | QR Box Size (pixels)
    |--------------------------------------------------------------------------
    |
    | The size of the scanning area box overlay.
    |
    */
    'qrbox_size' => 250,

    /*
    |--------------------------------------------------------------------------
    | Show Preview
    |--------------------------------------------------------------------------
    |
    | Whether to show the last scanned value as a preview.
    |
    */
    'show_preview' => true,

    /*
    |--------------------------------------------------------------------------
    | Modal Width
    |--------------------------------------------------------------------------
    |
    | Default modal width for the scanner. Options: xs, sm, md, lg, xl, 2xl, etc.
    |
    */
    'modal_width' => '2xl',

    /*
    |--------------------------------------------------------------------------
    | Beep on Scan
    |--------------------------------------------------------------------------
    |
    | Play a beep sound when a QR code is successfully scanned.
    |
    */
    'beep_on_scan' => true,

    /*
    |--------------------------------------------------------------------------
    | Vibrate on Scan
    |--------------------------------------------------------------------------
    |
    | Vibrate the device when a QR code is successfully scanned (mobile only).
    |
    */
    'vibrate_on_scan' => true,
];
