<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\PapScheme;

it('captures heavy single, work sets and work reps range', function (): void {
    $scheme = new PapScheme(heavySingle: 1, workSets: 3, workReps: '6-8');
    expect($scheme->heavySingle)->toBe(1)
        ->and($scheme->workSets)->toBe(3)
        ->and($scheme->workReps)->toBe('6-8');
});

it('allows multiple heavy singles when the protocol demands it', function (): void {
    $scheme = new PapScheme(heavySingle: 2, workSets: 4, workReps: '5');
    expect($scheme->heavySingle)->toBe(2)->and($scheme->workSets)->toBe(4);
});

it('rejects non-positive workSets', function (): void {
    new PapScheme(heavySingle: 1, workSets: 0, workReps: '6-8');
})->throws(InvalidArgumentException::class);
