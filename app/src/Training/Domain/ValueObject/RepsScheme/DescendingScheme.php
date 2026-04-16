<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class DescendingScheme extends RepsScheme
{
    /** @param int[] $drops */
    public function __construct(public array $drops)
    {
        if (count($drops) < 2) {
            throw new \InvalidArgumentException('DescendingScheme requires at least 2 drops.');
        }
        foreach ($drops as $reps) {
            if ($reps <= 0) {
                throw new \InvalidArgumentException('DescendingScheme.drops must contain positive integers.');
            }
        }
    }
}
