<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\WeekProgression;

it('stores a per-week map of progression data', function (): void {
    $progression = new WeekProgression([
        1 => ['distanceKm' => 3, 'pace' => '5:30/km'],
        2 => ['distanceKm' => 4, 'pace' => '5:20/km'],
    ]);
    expect($progression->weeks[1]['distanceKm'])->toBe(3)
        ->and($progression->weeks[2]['pace'])->toBe('5:20/km');
});

it('accepts any number of weeks', function (): void {
    $weeks = array_fill(1, 5, ['note' => 'adapt']);
    $progression = new WeekProgression($weeks);
    expect(count($progression->weeks))->toBe(5);
});

it('rejects empty weeks map', function (): void {
    new WeekProgression([]);
})->throws(InvalidArgumentException::class);

it('rejects non-positive or non-integer week keys', function (): void {
    new WeekProgression([0 => ['note' => 'invalid']]);
})->throws(InvalidArgumentException::class);
