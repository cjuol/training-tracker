<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class PapScheme extends RepsScheme
{
    public function __construct(public int $heavySingle, public int $workSets, public string $workReps)
    {
        if ($heavySingle <= 0) {
            throw new \InvalidArgumentException('PapScheme.heavySingle must be positive.');
        }
        if ($workSets <= 0) {
            throw new \InvalidArgumentException('PapScheme.workSets must be positive.');
        }
        if ($workReps === '') {
            throw new \InvalidArgumentException('PapScheme.workReps cannot be empty.');
        }
    }
}
