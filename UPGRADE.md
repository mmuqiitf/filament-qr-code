# Upgrade Guide

## Upgrading from 0.x to 1.0

### Breaking Changes

None - this is the initial release.

## General Upgrade Steps

1. Update your `composer.json`:

```json
"mmuqiitf/filament-qr-code": "^1.0"
```

2. Run composer update:

```bash
composer update mmuqiitf/filament-qr-code
```

3. Clear caches:

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

4. Re-publish assets if you've customized them:

```bash
php artisan vendor:publish --tag=filament-qr-code-config --force
php artisan vendor:publish --tag=filament-qr-code-views --force
```
