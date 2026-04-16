<?php

declare(strict_types=1);

namespace App\Training\Enum;

enum SetGroupType: string
{
    case STRAIGHT = 'STRAIGHT';
    case AMRAP = 'AMRAP';
    case DESCENDING = 'DESCENDING';
    case CLUSTER = 'CLUSTER';
    case REST_PAUSE = 'REST_PAUSE';
    case PAP = 'PAP';
    case SUPERSET = 'SUPERSET';
    case POLIQUIN_TRISET = 'POLIQUIN_TRISET';
    case PARTIAL_PLUS_FULL = 'PARTIAL_PLUS_FULL';
    case SCD = 'SCD';
}
