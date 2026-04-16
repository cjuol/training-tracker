<?php

declare(strict_types=1);

namespace App\Training\Enum;

enum SetModifier: string
{
    case NONE = 'NONE';
    case MANTEN_PESO = 'MANTEN_PESO';
    case REDUCE_PESO = 'REDUCE_PESO';
}
