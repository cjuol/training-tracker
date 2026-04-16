<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\PartialPlusFullScheme;

it('captures partial and full rep counts', function (): void {
    $scheme = new PartialPlusFullScheme(partialReps: 8, fullReps: 4);
    expect($scheme->partialReps)->toBe(8)->and($scheme->fullReps)->toBe(4);
});

it('allows any positive combination', function (): void {
    $scheme = new PartialPlusFullScheme(partialReps: 3, fullReps: 10);
    expect($scheme->partialReps)->toBe(3)->and($scheme->fullReps)->toBe(10);
});

it('rejects zero reps on either side', function (): void {
    new PartialPlusFullScheme(partialReps: 0, fullReps: 5);
})->throws(InvalidArgumentException::class);
