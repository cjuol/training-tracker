<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\AmrapScheme;
use App\Training\Domain\ValueObject\RepsScheme\RepsScheme;

it('is a RepsScheme subtype', function (): void {
    expect(new AmrapScheme(target: 8, min: 7))->toBeInstanceOf(RepsScheme::class);
});

it('exposes target and min as distinct readonly fields', function (): void {
    $scheme = new AmrapScheme(target: 8, min: 7);
    expect($scheme->target)->toBe(8)->and($scheme->min)->toBe(7);
});

it('allows looser ranges where min < target significantly', function (): void {
    $scheme = new AmrapScheme(target: 12, min: 8);
    expect($scheme->target)->toBe(12)->and($scheme->min)->toBe(8);
});

it('rejects min greater than target', function (): void {
    new AmrapScheme(target: 6, min: 9);
})->throws(InvalidArgumentException::class);

it('rejects non-positive target', function (): void {
    new AmrapScheme(target: 0, min: 0);
})->throws(InvalidArgumentException::class);
