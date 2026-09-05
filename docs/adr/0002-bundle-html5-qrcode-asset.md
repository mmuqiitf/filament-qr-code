# Bundle html5-qrcode via Vite and Filament Asset Pipeline

To ensure the package functions in offline, air-gapped, and strict enterprise security environments without CDN latency or unpkg risks, we decided to bundle `html5-qrcode` locally into compiled JavaScript assets distributed with the package. These assets will be registered into Filament's asset manager using `FilamentAsset::register()`. This decision provides reliable camera selection, torch/zoom capabilities, and file upload fallback with zero external network dependencies.
