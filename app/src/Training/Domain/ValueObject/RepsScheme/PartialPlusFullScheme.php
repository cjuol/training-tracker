<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class PartialPlusFullScheme extends RepsScheme
{
    public function __construct(public int $partialReps, public int $fullReps)
    {
        if ($partialReps <= 0 || $fullReps <= 0) {
            throw new \InvalidArgumentException('PartialPlusFullScheme reps must be positive.');
        }
    }
}
