<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject;

final readonly class WeekProgression
{
    /**
     * Maps week number (1-indexed) to free-form progression data for that week.
     * Used mainly by cardio blocks where volume/intensity change weekly.
     *
     * @param array<int, array<string, mixed>> $weeks
     */
    public function __construct(public array $weeks)
    {
        if ($weeks === []) {
            throw new \InvalidArgumentException('WeekProgression.weeks cannot be empty.');
        }
        foreach (array_keys($weeks) as $week) {
            if ($week < 1) {
                throw new \InvalidArgumentException('WeekProgression week keys must be positive integers.');
            }
        }
    }
}
