<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\RepsScheme;
use App\Training\Domain\ValueObject\RepsScheme\StraightScheme;

it('is a RepsScheme subtype', function (): void {
    expect(new StraightScheme('9-13'))->toBeInstanceOf(RepsScheme::class);
});

it('preserves the reps literal exactly as written in the PDF', function (): void {
    expect((new StraightScheme('9-13'))->reps)->toBe('9-13')
        ->and((new StraightScheme('5'))->reps)->toBe('5')
        ->and((new StraightScheme('8-12'))->reps)->toBe('8-12');
});

it('is immutable once constructed', function (): void {
    $scheme = new StraightScheme('9-13');
    $reflection = new ReflectionClass($scheme);
    expect($reflection->isReadOnly())->toBeTrue();
});

it('rejects an empty reps string', function (): void {
    new StraightScheme('');
})->throws(InvalidArgumentException::class);
