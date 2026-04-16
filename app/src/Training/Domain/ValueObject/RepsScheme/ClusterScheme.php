<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class ClusterScheme extends RepsScheme
{
    public function __construct(public int $subSets, public int $reps, public int $intraRestSeconds)
    {
        if ($subSets < 2) {
            throw new \InvalidArgumentException('ClusterScheme requires at least 2 subSets.');
        }
        if ($reps <= 0) {
            throw new \InvalidArgumentException('ClusterScheme.reps must be positive.');
        }
        if ($intraRestSeconds < 0) {
            throw new \InvalidArgumentException('ClusterScheme.intraRestSeconds cannot be negative.');
        }
    }
}
