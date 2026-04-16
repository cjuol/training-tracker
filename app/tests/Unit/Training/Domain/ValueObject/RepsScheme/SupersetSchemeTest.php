<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\SupersetScheme;

it('points to its paired letter', function (): void {
    $scheme = new SupersetScheme(pairedWith: 'A2');
    expect($scheme->pairedWith)->toBe('A2');
});

it('rejects an empty paired letter', function (): void {
    new SupersetScheme(pairedWith: '');
})->throws(InvalidArgumentException::class);

it('rejects a paired letter that does not match exercise letter format', function (): void {
    new SupersetScheme(pairedWith: '123lower');
})->throws(InvalidArgumentException::class);
