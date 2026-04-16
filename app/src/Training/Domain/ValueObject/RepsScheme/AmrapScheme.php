<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class AmrapScheme extends RepsScheme
{
    public function __construct(public int $target, public int $min)
    {
        if ($target <= 0) {
            throw new \InvalidArgumentException('AmrapScheme.target must be positive.');
        }
        if ($min > $target) {
            throw new \InvalidArgumentException('AmrapScheme.min cannot exceed target.');
        }
    }
}
