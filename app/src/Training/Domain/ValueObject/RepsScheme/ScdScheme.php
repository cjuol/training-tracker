<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

final readonly class ScdScheme extends RepsScheme
{
    /**
     * @param int[] $descending
     * @param int[] $holdSeconds
     */
    public function __construct(public array $descending, public array $holdSeconds)
    {
        if ($descending === []) {
            throw new \InvalidArgumentException('ScdScheme.descending cannot be empty.');
        }
        if (count($descending) !== count($holdSeconds)) {
            throw new \InvalidArgumentException('ScdScheme.descending and holdSeconds must have the same length.');
        }
    }
}
