<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Mmuqiitf\FilamentQrCode\Http\Middleware\ThrottleQrImage;
use Mmuqiitf\FilamentQrCode\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImageRouteMiddlewareTest extends TestCase
{
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('qr-code.image_route.middleware', [SubstituteBindings::class, 5, '']);
    }

    #[Test]
    public function test_image_routes_append_string_middleware_from_config(): void
    {
        $expected = ['signed', ThrottleQrImage::class, SubstituteBindings::class];

        $this->assertSame($expected, Route::getRoutes()->getByName('filamentQrCode.image')->gatherMiddleware());
        $this->assertSame($expected, Route::getRoutes()->getByName('filamentQrCode.barcode')->gatherMiddleware());
    }
}
