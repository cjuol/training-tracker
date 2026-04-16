<?php

declare(strict_types=1);

namespace App\Training\Enum;

enum MuscleGroup: string
{
    case CHEST = 'CHEST';
    case BACK = 'BACK';
    case SHOULDER_ANT = 'SHOULDER_ANT';
    case SHOULDER_LAT = 'SHOULDER_LAT';
    case SHOULDER_POST = 'SHOULDER_POST';
    case BICEPS = 'BICEPS';
    case TRICEPS = 'TRICEPS';
    case FOREARMS = 'FOREARMS';
    case QUADS = 'QUADS';
    case HAMSTRINGS = 'HAMSTRINGS';
    case GLUTES = 'GLUTES';
    case CALVES = 'CALVES';
    case CORE = 'CORE';
}
