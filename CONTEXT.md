# Filament QR Code

A modular Filament v5 package providing QR code generation, interactive camera reading, sequential multi-field entry, and hardware scanner device integration.

## Language

### Core Components

**QR Generator**:
A backend service and UI component that encodes data into QR code images (SVG/PNG) for display in Filament Forms, Infolists, and Tables.
_Avoid_: QR maker, barcode creator

**QR Scanner Field**:
A Filament form field that captures QR code values through a camera stream, image upload, or manual entry.
_Avoid_: QR input, barcode box

**QR Code Display**:
A Filament form component that renders a dynamic QR code generated from record or form state.
_Avoid_: QR preview field, barcode renderer

**QR Entry**:
An Infolist entry component that displays an encoded QR code inside a view schema.
_Avoid_: QR info field, view QR

**QR Column**:
A Table column component that displays an encoded QR thumbnail with click-to-enlarge and download capabilities.
_Avoid_: Barcode column, QR table cell

**Hardware Wedge Scanner**:
A physical handheld USB/Bluetooth scanner that inputs scanned characters into the browser via rapid keystroke emulation followed by a terminator key.
_Avoid_: Barcode gun, physical reader

**Wedge Interceptor**:
An Alpine.js directive and event listener that buffers high-speed keystroke bursts from hardware scanners, prevents accidental form submissions, and routes values to the active field.
_Avoid_: Keyboard listener, input hook

**Station Listener**:
A page-level or widget-level keyboard wedge listener that captures scans globally across the viewport and automatically routes inputs to the active or first empty registered field.
_Avoid_: Global watcher, window listener

### Symbologies & Formats

**Barcode Format**:
The optical symbology used to encode data, encompassing 2D matrices (QR Code, Data Matrix, Aztec) and 1D linear barcodes (Code 128, Code 39, EAN-13, UPC-A).
_Avoid_: Code type, barcode variety

### Scanning Modes & Containers

**Sequential Field Scanning**:
An automated workflow that advances focus or target destination to the next specified form field immediately upon successfully capturing a code.
_Avoid_: Multi-step scan, chained input

**Scan Sequence Container**:
A composite layout or schema container that orchestrates a shared camera viewfinder, scanning progress, and sequential focus transitions across multiple form fields.
_Avoid_: Step container, multi-input group

**Batch Collector Scanning**:
A continuous scanning mode that remains active to append multiple scanned QR items into a Filament repeater or array state without interruption.
_Avoid_: Bulk scan, multi-scan modal

**QR Collect Action**:
A specialized Filament Action designed for Repeaters and Tables to launch a continuous batch scanning session.
_Avoid_: Batch scan button, bulk add action

**Scan Feedback**:
Immediate sensory confirmation (synthesized Web Audio tone and haptic vibration) triggered upon successful scan decoding.
_Avoid_: Beep effect, buzzer
