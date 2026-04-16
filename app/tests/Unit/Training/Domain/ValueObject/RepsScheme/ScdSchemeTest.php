<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\ScdScheme;

it('pairs descending reps with hold seconds of the same length', function (): void {
    $scheme = new ScdScheme(descending: [5, 4, 3, 2, 1], holdSeconds: [1, 2, 3, 4, 5]);
    expect($scheme->descending)->toBe([5, 4, 3, 2, 1])
        ->and($scheme->holdSeconds)->toBe([1, 2, 3, 4, 5]);
});

it('allows a shorter SCD sequence', function (): void {
    $scheme = new ScdScheme(descending: [3, 2, 1], holdSeconds: [2, 4, 6]);
    expect($scheme->descending)->toHaveCount(3)->and($scheme->holdSeconds)->toHaveCount(3);
});

it('rejects descending and holdSeconds of different lengths', function (): void {
    new ScdScheme(descending: [5, 4, 3], holdSeconds: [1, 2]);
})->throws(InvalidArgumentException::class);

it('rejects empty descending sequence', function (): void {
    new ScdScheme(descending: [], holdSeconds: []);
})->throws(InvalidArgumentException::class);
