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
    'default_camera_facing' => env('QR_CODE_CAMERA_FACING', 'environment'),

    /*
    |--------------------------------------------------------------------------
    | Default FPS (Frames Per Second)
    |--------------------------------------------------------------------------
    |
    | The default FPS for the camera feed. Higher values are smoother but use
    | more CPU. Valid range: 5-60. Recommended: 30 for most devices.
    |
    */
    'default_fps' => env('QR_CODE_DEFAULT_FPS', 30),

    /*
    |--------------------------------------------------------------------------
    | Show Camera Selector
    |--------------------------------------------------------------------------
    |
    | Whether to show the camera selector dropdown in the UI.
    | When enabled, users can switch between available cameras.
    |
    */
    'show_camera_selector' => env('QR_CODE_SHOW_CAMERA_SELECTOR', true),

    /*
    |--------------------------------------------------------------------------
    | Show FPS Control
    |--------------------------------------------------------------------------
    |
    | Whether to show the FPS control input in the UI.
    | When enabled, users can adjust the camera frame rate.
    |
    */
    'show_fps_control' => env('QR_CODE_SHOW_FPS_CONTROL', true),

    /*
    |--------------------------------------------------------------------------
    | Default Scan Mode
    |--------------------------------------------------------------------------
    |
    | The default scan behavior after a successful scan.
    | Options: 'single', 'continuous', 'sequence'
    |
    */
    'scan_mode' => env('QR_CODE_SCAN_MODE', 'single'),

    /*
    |--------------------------------------------------------------------------
    | Scan Delay (milliseconds)
    |--------------------------------------------------------------------------
    |
    | The debounce delay between scans to prevent duplicate reads.
    |
    */
    'scan_delay' => env('QR_CODE_SCAN_DELAY', 1500),

    /*
    |--------------------------------------------------------------------------
    | Frames Per Second (Legacy)
    |--------------------------------------------------------------------------
    |
    | Legacy FPS setting. Use 'default_fps' instead.
    | Kept for backward compatibility.
    |
    */
    'fps' => env('QR_CODE_FPS', 30),

    /*
    |--------------------------------------------------------------------------
    | Camera Facing (Legacy)
    |--------------------------------------------------------------------------
    |
    | Legacy camera facing setting. Use 'default_camera_facing' instead.
    | Kept for backward compatibility.
    |
    */
    'camera_facing' => env('QR_CODE_CAMERA_FACING_LEGACY', 'environment'),

    /*
    |--------------------------------------------------------------------------
    | QR Box Size (pixels)
    |--------------------------------------------------------------------------
    |
    | The size of the scanning area box overlay.
    |
    */
    'qrbox_size' => env('QR_CODE_QRBOX_SIZE', 250),

    /*
    |--------------------------------------------------------------------------
    | Show Preview
    |--------------------------------------------------------------------------
    |
    | Whether to show the last scanned value as a preview.
    |
    */
    'show_preview' => env('QR_CODE_SHOW_PREVIEW', true),

    /*
    |--------------------------------------------------------------------------
    | Modal Width
    |--------------------------------------------------------------------------
    |
    | Default modal width for the scanner. Options: xs, sm, md, lg, xl, 2xl, etc.
    |
    */
    'modal_width' => env('QR_CODE_MODAL_WIDTH', '2xl'),

    /*
    |--------------------------------------------------------------------------
    | Beep on Scan
    |--------------------------------------------------------------------------
    |
    | Play a beep sound when a QR code is successfully scanned.
    |
    */
    'beep_on_scan' => env('QR_CODE_BEEP_ON_SCAN', true),

    /*
    |--------------------------------------------------------------------------
    | Vibrate on Scan
    |--------------------------------------------------------------------------
    |
    | Vibrate the device when a QR code is successfully scanned (mobile only).
    |
    */
    'vibrate_on_scan' => env('QR_CODE_VIBRATE_ON_SCAN', true),
];
