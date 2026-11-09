<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Support\BarcodeRenderSpec;

class BarcodeImageController
{
    public function __invoke(Request $request): Response
    {
        // Invalid input is always a 422: an <img> tag cannot follow a
        // validation redirect, and a bad colour must not surface as a 500.
        $validator = Validator::make($request->query(), [
            'data' => ['required', 'string', 'max:2000'],
            'type' => ['sometimes', 'string', 'max:32'],
            'format' => ['sometimes', 'string', 'in:svg,png'],
            'barWidth' => ['sometimes', 'integer', 'min:1', 'max:64'],
            'height' => ['sometimes', 'integer', 'min:10', 'max:2000'],
            'margin' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'foreground' => ['sometimes', 'string', 'max:7', 'regex:/^#*([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'background' => ['sometimes', 'string', 'max:7', 'regex:/^#*([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'showText' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            abort(422, (string) $validator->errors()->first());
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        if ((bool) config('qr-code.image_route.strict_colors', false)) {
            foreach (['foreground', 'background'] as $key) {
                $color = $validated[$key] ?? null;
                if (is_string($color) && ! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                    abort(422, "The {$key} colour must be #rrggbb.");
                }
            }
        }

        $data = $validated['data'] ?? null;
        if (! is_string($data)) {
            abort(422, 'The data field is required.');
        }

        try {
            $service = BarcodeRenderSpec::fromSignedParams($validated)->toService()->generate($data);
        } catch (InvalidBarcodeException $e) {
            abort(422, $e->reason);
        } catch (InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        return new Response(
            $service->getRaw(),
            200,
            [
                'Content-Type' => $service->getFormat()->getMimeType(),
                'Cache-Control' => 'private, max-age='.self::maxAge().', immutable',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private static function maxAge(): int
    {
        $ttl = config('qr-code.image_route.ttl');

        return is_numeric($ttl) && (int) $ttl > 0 ? min(86400, (int) $ttl * 60) : 86400;
    }
}
