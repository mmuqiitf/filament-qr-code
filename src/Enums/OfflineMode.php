<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

/** What a camera surface does when the browser is offline. */
enum OfflineMode: string
{
    /** Show a banner. Local scans still update state; server checks still run and can fail. */
    case Banner = 'banner';

    /** Show the banner and reject new scans until the browser is online again. */
    case Block = 'block';

    /** No banner and no extra rejection. */
    case Off = 'off';
}
