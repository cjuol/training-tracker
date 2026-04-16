<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject;

final readonly class RirScheme
{
    /**
     * Stores the RIR pattern as a list of per-series tokens. Each token is either
     * a numeric band ("01", "12"), a single digit ("2"), or "F" for failure.
     * Examples from the PDF:
     *   ["12"]                           → every series RIR 1-2
     *   ["12", "01", "01"]               → series 1 at 12, series 2 and 3 at 01
     *   ["F"]                            → AMRAP to failure
     *
     * @param string[] $perSeries
     */
    public function __construct(public array $perSeries)
    {
        if ($perSeries === []) {
            throw new \InvalidArgumentException('RirScheme.perSeries cannot be empty.');
        }
        foreach ($perSeries as $token) {
            if (preg_match('/^(F|[0-9]{1,2})$/', $token) !== 1) {
                throw new \InvalidArgumentException(
                    sprintf('RirScheme token "%s" must be "F" or 1-2 digits.', $token)
                );
            }
        }
    }
}
