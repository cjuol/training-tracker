<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\DescendingScheme;

it('accepts the classic "6+10" pair from the PDF', function (): void {
    $scheme = new DescendingScheme([6, 10]);
    expect($scheme->drops)->toBe([6, 10]);
});

it('accepts extended "6+8+11" triples', function (): void {
    $scheme = new DescendingScheme([6, 8, 11]);
    expect($scheme->drops)->toBe([6, 8, 11])->and(count($scheme->drops))->toBe(3);
});

it('requires at least two drops', function (): void {
    new DescendingScheme([6]);
})->throws(InvalidArgumentException::class);

it('rejects empty drops array', function (): void {
    new DescendingScheme([]);
})->throws(InvalidArgumentException::class);

it('rejects non-positive reps inside drops', function (): void {
    new DescendingScheme([6, 0]);
})->throws(InvalidArgumentException::class);
