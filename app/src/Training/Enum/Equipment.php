<?php

declare(strict_types=1);

namespace App\Training\Enum;

enum Equipment: string
{
    case BARBELL = 'BARBELL';
    case DUMBBELL = 'DUMBBELL';
    case MACHINE_PLATES = 'MACHINE_PLATES';
    case MACHINE_SELECTORIZED = 'MACHINE_SELECTORIZED';
    case CABLE = 'CABLE';
    case KETTLEBELL = 'KETTLEBELL';
    case BODYWEIGHT = 'BODYWEIGHT';
    case BANDS = 'BANDS';
    case SMITH = 'SMITH';
    case OTHER = 'OTHER';
}
