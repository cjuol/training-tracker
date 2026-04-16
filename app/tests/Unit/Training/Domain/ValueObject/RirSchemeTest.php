<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RirScheme;

it('captures a single RIR band shared by all series', function (): void {
    $scheme = new RirScheme(['12']);
    expect($scheme->perSeries)->toBe(['12']);
});

it('captures progressive intensity as per-series tokens', function (): void {
    $scheme = new RirScheme(['12', '01', '01']);
    expect($scheme->perSeries)->toHaveCount(3)->and($scheme->perSeries[2])->toBe('01');
});

it('allows "F" to signal failure (AMRAP)', function (): void {
    $scheme = new RirScheme(['F']);
    expect($scheme->perSeries)->toBe(['F']);
});

it('rejects empty perSeries', function (): void {
    new RirScheme([]);
})->throws(InvalidArgumentException::class);

it('rejects tokens outside the grammar (letters other than F, too many digits)', function (): void {
    new RirScheme(['XYZ']);
})->throws(InvalidArgumentException::class);
