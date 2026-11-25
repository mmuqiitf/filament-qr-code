<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        {!! $css !!}
        @if ($pageWidth && $pageHeight)
        @page { size: {{ $pageWidth }} {{ $pageHeight }}; margin: 0; }
        @endif
        body { margin: 0; background: #fff; color: #111; }
    </style>
</head>
<body>
    <div class="filament-qr-no-print" style="padding: 1rem; text-align: center;">
        <button type="button" onclick="window.print()">{{ __('filament-qr-code::ui.print_sheet') }}</button>
    </div>
    {!! $sheetHtml !!}
</body>
</html>
