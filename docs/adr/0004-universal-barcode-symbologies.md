# Support Universal 1D and 2D Barcode Symbologies

While the package is primarily branded around QR codes, modern manufacturing and logistics workflows require both 2D QR codes and 1D linear barcodes (such as Code 128 for employee badges and Code 39 or EAN-13 for serials). We decided to leverage the underlying `html5-qrcode` engine's native decoding matrix to expose a unified `BarcodeFormat` enum and fluent configuration (`->formats([...])`), allowing developers to use this single package for both 1D and 2D barcodes with zero additional dependencies.
