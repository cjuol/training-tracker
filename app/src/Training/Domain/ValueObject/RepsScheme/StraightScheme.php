<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class StraightScheme extends RepsScheme
{
    public function __construct(public string $reps)
    {
        if ($reps === '') {
            throw new \InvalidArgumentException('StraightScheme.reps cannot be empty.');
        }
    }
}
