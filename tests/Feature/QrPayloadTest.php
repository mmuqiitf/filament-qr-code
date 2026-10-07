<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Support\QrPayload;

it('builds WiFi payloads with escaping', function () {
    expect(QrPayload::wifi('Office;Net', 'p@ss:word', 'WPA'))->toBe('WIFI:T:WPA;S:Office\\;Net;P:p@ss\\:word;;')
        ->and(QrPayload::wifi('Open Hall', null, 'nopass', true))->toBe('WIFI:T:nopass;S:Open Hall;H:true;;');
});

it('builds contact and location payloads', function () {
    expect(QrPayload::mailto('ops@example.com', 'Stock alert', 'Bin 12 empty'))->toBe('mailto:ops@example.com?subject=Stock%20alert&body=Bin%2012%20empty')
        ->and(QrPayload::sms('+621234567', 'Arrived'))->toBe('SMSTO:+621234567:Arrived')
        ->and(QrPayload::geo(-6.2, 106.8, 'Warehouse 7'))->toBe('geo:-6.2,106.8?q=Warehouse%207');
});

it('builds escaped vCards', function () {
    $vcard = QrPayload::vcard([
        'firstName' => 'Siti',
        'lastName' => 'Rahayu, Jr.',
        'organization' => 'Gudang;Utama',
        'phone' => '+621234567',
        'email' => 'siti@example.com',
    ]);

    expect($vcard)->toContain('N:Rahayu\\, Jr.;Siti;;;')
        ->and($vcard)->toContain('ORG:Gudang\\;Utama')
        ->and($vcard)->toContain('TEL;TYPE=CELL:+621234567')
        ->and($vcard)->toContain('END:VCARD');
});

it('encodes payload builders through the generator', function () {
    $uri = FilamentQrCode::make()
        ->generate(QrPayload::wifi('Shop Floor', 'secret-1'))
        ->toDataUri();

    expect($uri)->toStartWith('data:image/svg+xml;base64,');
});
