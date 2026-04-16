<?php

declare(strict_types=1);

use App\Training\Enum\Equipment;

it('exposes 10 equipment kinds covering the gym', function (): void {
    expect(Equipment::cases())->toHaveCount(10)
        ->and(array_map(fn (Equipment $e) => $e->value, Equipment::cases()))
        ->toMatchArray([
            'BARBELL', 'DUMBBELL', 'MACHINE_PLATES', 'MACHINE_SELECTORIZED',
            'CABLE', 'KETTLEBELL', 'BODYWEIGHT', 'BANDS', 'SMITH', 'OTHER',
        ]);
});

it('distinguishes plate-loaded from selectorized machines', function (): void {
    expect(Equipment::MACHINE_PLATES)->not->toBe(Equipment::MACHINE_SELECTORIZED);
});
