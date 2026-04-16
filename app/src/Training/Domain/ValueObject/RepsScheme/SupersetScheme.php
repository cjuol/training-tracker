<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class SupersetScheme extends RepsScheme
{
    public function __construct(public string $pairedWith)
    {
        if ($pairedWith === '') {
            throw new \InvalidArgumentException('SupersetScheme.pairedWith cannot be empty.');
        }
        if (preg_match('/^[A-Z][0-9]?$/', $pairedWith) !== 1) {
            throw new \InvalidArgumentException(
                'SupersetScheme.pairedWith must match exercise letter format (A, A1, B2, …).'
            );
        }
    }
}
