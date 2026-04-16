<?php

declare(strict_types=1);

namespace App\Training\Enum;

enum MesocycleGoal: string
{
    case HYROX = 'HYROX';
    case FAT_LOSS_RUNNING = 'FAT_LOSS_RUNNING';
    case HYBRID = 'HYBRID';
    case STRENGTH = 'STRENGTH';
    case HYPERTROPHY = 'HYPERTROPHY';
    case MAINTENANCE = 'MAINTENANCE';
}
