<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\RestPauseScheme;

it('captures initial reps, microSet ranges and intra-rest seconds', function (): void {
    $scheme = new RestPauseScheme(initial: 8, microSets: [[3, 4], [1, 2]], intraRestSeconds: 25);
    expect($scheme->initial)->toBe(8)
        ->and($scheme->microSets)->toBe([[3, 4], [1, 2]])
        ->and($scheme->intraRestSeconds)->toBe(25);
});

it('accepts a short rest-pause with a single microSet', function (): void {
    $scheme = new RestPauseScheme(initial: 6, microSets: [[2, 3]], intraRestSeconds: 20);
    expect($scheme->microSets)->toHaveCount(1);
});

it('rejects non-positive initial reps', function (): void {
    new RestPauseScheme(initial: 0, microSets: [[3, 4]], intraRestSeconds: 25);
})->throws(InvalidArgumentException::class);

it('rejects malformed microSet tuples', function (): void {
    new RestPauseScheme(initial: 8, microSets: [[3]], intraRestSeconds: 25);
})->throws(InvalidArgumentException::class);
