# Contributing

Thanks for contributing to `mmuqiitf/filament-qr-code`. This file covers the maintainer workflow; user documentation lives in [README.md](README.md) and [docs/](docs/).

## Setup

```bash
composer install
npm install
```

Tests run against sqlite via Orchestra Testbench — no database setup needed. PNG/text-overlay tests need GD (or Imagick) plus system fonts (`fonts-dejavu-core` on Debian); CI installs them.

## Testing

```bash
composer test
```

Single file or test:

```bash
vendor/bin/pest tests/Unit/QrCodeServiceTest.php
vendor/bin/pest --filter="can generate a PNG QR code"
```

Frontend (Vitest over `resources/js/**/*.test.js` — interceptor routing, sanitize, and the single-beep ownership):

```bash
npm test
```

## Static analysis

PHPStan level 9 over `src/` + `config/` only:

```bash
composer analyse
```

Keep new PHP files under those paths typed to level 9 with `declare(strict_types=1)`.

## Code style

Laravel Pint (preset `laravel`, plus strict types and ordered imports):

```bash
composer format      # fix
composer test-style  # check (what CI runs)
```

## Frontend

After any `resources/js` or `resources/css` change:

```bash
npm run build
```

Commit `resources/dist/` with your PR — it is what Filament serves, so never hand-edit it. Host apps pick it up via `php artisan filament:assets`.

## Pull requests

- One concern per PR; include Pest coverage for behavior changes.
- Follow the vocabulary in [CONTEXT.md](CONTEXT.md) for new APIs and docs.
- UI strings go under the `filament-qr-code::ui` translation namespace (`resources/lang/`).
