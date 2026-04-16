<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class RestPauseScheme extends RepsScheme
{
    /** @param array<int, array<int, int>> $microSets — each inner array is a [min, max] tuple. */
    public function __construct(public int $initial, public array $microSets, public int $intraRestSeconds)
    {
        if ($initial <= 0) {
            throw new \InvalidArgumentException('RestPauseScheme.initial must be positive.');
        }
        if ($microSets === []) {
            throw new \InvalidArgumentException('RestPauseScheme.microSets cannot be empty.');
        }
        foreach ($microSets as $tuple) {
            if (count($tuple) !== 2) {
                throw new \InvalidArgumentException('Each microSet must be a [min, max] tuple.');
            }
        }
        if ($intraRestSeconds < 0) {
            throw new \InvalidArgumentException('RestPauseScheme.intraRestSeconds cannot be negative.');
        }
    }
}
