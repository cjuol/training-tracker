<?php

declare(strict_types=1);

use App\Training\Enum\MuscleGroup;

it('exposes 13 muscle groups aligned with the exercise catalog', function (): void {
    expect(MuscleGroup::cases())->toHaveCount(13);
});

it('distinguishes shoulder heads for targeted volume tracking', function (): void {
    $values = array_map(fn (MuscleGroup $m) => $m->value, MuscleGroup::cases());
    expect($values)->toContain('SHOULDER_ANT')
        ->and($values)->toContain('SHOULDER_LAT')
        ->and($values)->toContain('SHOULDER_POST');
});

it('includes CORE separately from abs to support HYROX-style conditioning', function (): void {
    expect(MuscleGroup::from('CORE'))->toBe(MuscleGroup::CORE);
});
