<?php

declare(strict_types=1);

use Workbench\App\Filament\Pages\ProductEdit;

it('previews a barcode from the SKU being edited', function (): void {
    $source = (string) file_get_contents((new ReflectionClass(ProductEdit::class))->getFileName());

    expect($source)
        ->toContain("TextInput::make('sku')->label('SKU')->live()")
        ->toContain('BarcodeDisplay::make(\'barcode\')')
        ->toContain("filled(\$get('sku'))");
});
