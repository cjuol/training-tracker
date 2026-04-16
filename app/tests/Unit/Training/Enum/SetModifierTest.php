<?php

declare(strict_types=1);

use App\Training\Enum\SetModifier;

it('exposes NONE, MANTEN_PESO, REDUCE_PESO', function (): void {
    expect(SetModifier::cases())->toHaveCount(3)
        ->and(array_map(fn (SetModifier $m) => $m->value, SetModifier::cases()))
        ->toMatchArray(['NONE', 'MANTEN_PESO', 'REDUCE_PESO']);
});

it('defaults to NONE when no modifier specified', function (): void {
    expect(SetModifier::NONE->value)->toBe('NONE');
});
