<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScanCheckProduct extends Model
{
    protected $table = 'scan_check_products';

    protected $guarded = [];

    public $timestamps = false;

    public static function createTableWithProducts(): void
    {
        Schema::dropIfExists('scan_check_products');
        Schema::create('scan_check_products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('stock')->default(0);
        });

        self::create(['sku' => 'SKU-1', 'active' => true, 'stock' => 5]);
        self::create(['sku' => 'SKU-OLD', 'active' => false, 'stock' => 5]);
        self::create(['sku' => 'SKU-EMPTY', 'active' => true, 'stock' => 0]);
    }
}
