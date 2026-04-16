<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class PoliquinTrisetScheme extends RepsScheme
{
    public function __construct(public int $a, public int $b, public int $c)
    {
        foreach (['a' => $a, 'b' => $b, 'c' => $c] as $arm => $reps) {
            if ($reps <= 0) {
                throw new \InvalidArgumentException(sprintf('PoliquinTrisetScheme.%s must be positive.', $arm));
            }
        }
    }
}
