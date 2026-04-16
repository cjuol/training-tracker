<?php

declare(strict_types=1);

use App\Training\Enum\MesocycleGoal;

it('exposes all documented goals', function (): void {
    expect(MesocycleGoal::cases())->toHaveCount(6)
        ->and(array_map(fn (MesocycleGoal $g) => $g->value, MesocycleGoal::cases()))
        ->toMatchArray(['HYROX', 'FAT_LOSS_RUNNING', 'HYBRID', 'STRENGTH', 'HYPERTROPHY', 'MAINTENANCE']);
});

it('round-trips canonical string values', function (): void {
    expect(MesocycleGoal::from('HYROX'))->toBe(MesocycleGoal::HYROX)
        ->and(MesocycleGoal::from('STRENGTH'))->toBe(MesocycleGoal::STRENGTH);
});

it('rejects unknown values', function (): void {
    MesocycleGoal::from('UNKNOWN_GOAL');
})->throws(ValueError::class);
