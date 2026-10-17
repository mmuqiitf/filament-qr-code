<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum InvalidSequenceBehavior: string
{
    case JumpToStep = 'jump_to_step';
    case Restart = 'restart';
}
