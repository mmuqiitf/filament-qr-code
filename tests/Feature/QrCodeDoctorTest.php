<?php

declare(strict_types=1);

it('reports install health through the doctor command', function () {
    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain('PNG backend available')
        ->expectsOutputToContain('Compiled assets are newer than sources')
        ->expectsOutputToContain('Signed preview route registered');
});
