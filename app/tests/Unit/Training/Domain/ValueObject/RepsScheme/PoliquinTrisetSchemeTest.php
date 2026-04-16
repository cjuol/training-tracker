<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\PoliquinTrisetScheme;

it('captures three distinct rep counts for the triset arms', function (): void {
    $scheme = new PoliquinTrisetScheme(a: 6, b: 12, c: 25);
    expect($scheme->a)->toBe(6)->and($scheme->b)->toBe(12)->and($scheme->c)->toBe(25);
});

it('allows different classic trisets like 5-10-20', function (): void {
    $scheme = new PoliquinTrisetScheme(a: 5, b: 10, c: 20);
    expect([$scheme->a, $scheme->b, $scheme->c])->toBe([5, 10, 20]);
});

it('rejects zero or negative reps on any arm', function (): void {
    new PoliquinTrisetScheme(a: 0, b: 12, c: 25);
})->throws(InvalidArgumentException::class);
