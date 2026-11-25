<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Http\Controllers;

use Illuminate\Http\Response;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheetStore;

/** @internal */
class LabelSheetController
{
    public function __invoke(string $token): Response
    {
        $html = LabelSheetStore::get($token);

        abort_if($html === null, 404);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
