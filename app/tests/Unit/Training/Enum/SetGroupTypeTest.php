<?php

declare(strict_types=1);

use App\Training\Enum\SetGroupType;

it('exposes 10 set group types covering the mesocycle grammar', function (): void {
    expect(SetGroupType::cases())->toHaveCount(10)
        ->and(array_map(fn (SetGroupType $t) => $t->value, SetGroupType::cases()))
        ->toMatchArray([
            'STRAIGHT', 'AMRAP', 'DESCENDING', 'CLUSTER', 'REST_PAUSE',
            'PAP', 'SUPERSET', 'POLIQUIN_TRISET', 'PARTIAL_PLUS_FULL', 'SCD',
        ]);
});

it('round-trips canonical string values', function (): void {
    expect(SetGroupType::from('AMRAP'))->toBe(SetGroupType::AMRAP)
        ->and(SetGroupType::from('REST_PAUSE'))->toBe(SetGroupType::REST_PAUSE);
});

it('rejects unknown types', function (): void {
    SetGroupType::from('PYRAMID');
})->throws(ValueError::class);
