# Security Policy

This policy covers **Filament QR & Barcode** (`mmuqiitf/filament-qr-code`).

## Supported Versions

Only the 1.x release line receives security fixes. 0.x is no longer supported, with no grace period: upgrade to 1.x (see [UPGRADE.md](UPGRADE.md)).

| Version | Supported |
| ------- | --------- |
| 1.x     | ✅        |
| 0.x     | ❌        |

## Scope

Reports are especially welcome for:

- **The signed image route** (`filamentQrCode.image`): signature or `ttl` expiry bypasses, rendering unsigned or tampered parameters, `throttle` bypasses, and cache or header issues that leak a render.
- **Logo paths** (`logo()`, `qrLogo()` and `qr-code.generator.logo_base_paths`): reading files outside the configured base paths, path traversal, or embedding non-image files.
- **Scanned input handling**: scanned or uploaded code values reaching HTML, JavaScript or the database unescaped or unvalidated (XSS, injection), hardware-scanner input bypassing field validation, or camera and image-upload decoding that can be abused.

Issues in Filament, Laravel or other dependencies should go to those projects. Report them here only if this package makes them exploitable.

## Reporting a Vulnerability

Please do **not** report security vulnerabilities through public GitHub issues, discussions, or pull requests.

Report them privately through GitHub Security Advisories instead: open the repository's [Security tab](https://github.com/mmuqiitf/filament-qr-code/security) and click **Report a vulnerability** (direct link: <https://github.com/mmuqiitf/filament-qr-code/security/advisories/new>).

Include as much of the following as you can:

- the affected version(s) and component (e.g. `QrScanner`, the signed image route, the JS bundle),
- a description of the issue and its impact,
- steps to reproduce or a minimal proof of concept.

You will receive an acknowledgement within 7 days. Once the issue is confirmed, a fix is prepared in a private advisory and released, and the advisory is published with credit to the reporter unless you prefer to stay anonymous.
