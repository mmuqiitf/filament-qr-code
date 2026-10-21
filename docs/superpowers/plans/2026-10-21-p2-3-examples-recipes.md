# P2.3 Examples and recipes: implementation plan

The spec is `docs/superpowers/specs/2026-10-21-p2-3-examples-recipes-design.md`.

## Package PR (`docs/p2-3-recipes`)

1. **Docs test, red.** Add `tests/Feature/RecipesTest.php`: lint every `php` block in `docs/recipes/*.md`, check the
   imported package classes exist, and check that package-builder methods exist. Add a fixture recipe with a bogus
   method to see it fail, then remove it.
2. **Recipes.** Update `pos.md`, then add `warehouse.md`, `manufacturing.md`, `generator-page.md`, `custom-input.md` and
   `styling.md`. Keep the docs test green.
3. **Links.** Link the recipes from README, reference.md ("Recipes") and customizing.md.
4. **UPGRADE.md** (0.5 to 0.6) and CHANGELOG Unreleased "Docs" entries.
5. **Checks.** Run Pest, Vitest, Pint and PHPStan, push, open the PR and watch CI.

## Example PR (`feat/p2-examples`)

1. **composer.** Require `dev-main`, set `minimum-stability dev` and `prefer-stable`, run
   `composer update mmuqiitf/filament-qr-code -W`, run `php artisan filament:assets`, and commit the lock and `public/`.
2. **PosCheckout.** Write a test that the scanner has no `wire:key` wrapper and switches change the rendered
   `data-qr-options`, then remove the workaround.
3. **GoodsReceiving.** Update the two order tests (red), then reorder the steps and use `resetSequence()`.
4. **Manufacturing.** Feature tests first: badge lookup through `BadgeDirectory`; unknown badge rejected; work order
   lookup; wrong component rejected by `ComponentBelongsToBom`; duplicate serial rejected; an incomplete BOM fails
   `validateAllUsing` on submit; `when()` asks for a rework reason only for rework orders; `onStepAccepted` feeds the
   activity list; the runtime buttons call `goToStep` and `resetSequence`; and the styling hooks render. Then add the
   models, migrations, factories, seeders, the stage, the directory and the page with its view.
5. **QR generator page.** Feature tests first: renders, each payload type builds the right string, invalid colour
   validation, SVG and PNG downloads. Then the page.
6. **Styling.** Add the published `sequence/step-chip` override, CSS variables for the station, and a render hook
   registered in a service provider.
7. **Browser tests.** Manufacturing wedge flow and generator preview.
8. **README** and the TODO for switching to `^0.6`.
9. **Checks.** Run Pint, the feature and browser suites (clean vendor, kill playwright), push, open the PR and watch CI.
