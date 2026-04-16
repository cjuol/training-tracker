<?php

declare(strict_types=1);

namespace App\Training\Domain\ValueObject\RepsScheme;

use App\Training\Domain\Exception\InvalidSchemeException;
use App\Training\Enum\SetGroupType;

final class RepsSchemeFactory
{
    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(SetGroupType $type, array $raw): RepsScheme
    {
        try {
            return match ($type) {
                SetGroupType::STRAIGHT => new StraightScheme(
                    self::requireString($raw, 'reps', $type),
                ),
                SetGroupType::AMRAP => new AmrapScheme(
                    target: self::requireInt($raw, 'target', $type),
                    min: self::requireInt($raw, 'min', $type),
                ),
                SetGroupType::DESCENDING => new DescendingScheme(
                    drops: self::requireIntArray($raw, 'drops', $type),
                ),
                SetGroupType::CLUSTER => new ClusterScheme(
                    subSets: self::requireInt($raw, 'subSets', $type),
                    reps: self::requireInt($raw, 'reps', $type),
                    intraRestSeconds: self::requireInt($raw, 'intraRestSeconds', $type),
                ),
                SetGroupType::REST_PAUSE => new RestPauseScheme(
                    initial: self::requireInt($raw, 'initial', $type),
                    microSets: self::requireArray($raw, 'microSets', $type),
                    intraRestSeconds: self::requireInt($raw, 'intraRestSeconds', $type),
                ),
                SetGroupType::PAP => new PapScheme(
                    heavySingle: self::requireInt($raw, 'heavySingle', $type),
                    workSets: self::requireInt($raw, 'workSets', $type),
                    workReps: self::requireString($raw, 'workReps', $type),
                ),
                SetGroupType::SUPERSET => new SupersetScheme(
                    pairedWith: self::requireString($raw, 'pairedWith', $type),
                ),
                SetGroupType::POLIQUIN_TRISET => new PoliquinTrisetScheme(
                    a: self::requireInt($raw, 'a', $type),
                    b: self::requireInt($raw, 'b', $type),
                    c: self::requireInt($raw, 'c', $type),
                ),
                SetGroupType::PARTIAL_PLUS_FULL => new PartialPlusFullScheme(
                    partialReps: self::requireInt($raw, 'partialReps', $type),
                    fullReps: self::requireInt($raw, 'fullReps', $type),
                ),
                SetGroupType::SCD => new ScdScheme(
                    descending: self::requireIntArray($raw, 'descending', $type),
                    holdSeconds: self::requireIntArray($raw, 'holdSeconds', $type),
                ),
            };
        } catch (\InvalidArgumentException $e) {
            throw new InvalidSchemeException(
                sprintf('Invalid %s scheme: %s', $type->value, $e->getMessage()),
                previous: $e,
            );
        }
    }

    /** @param array<string, mixed> $raw */
    private static function requireString(array $raw, string $key, SetGroupType $type): string
    {
        if (!isset($raw[$key]) || !is_string($raw[$key])) {
            throw new InvalidSchemeException(sprintf('%s scheme requires string "%s".', $type->value, $key));
        }
        return $raw[$key];
    }

    /** @param array<string, mixed> $raw */
    private static function requireInt(array $raw, string $key, SetGroupType $type): int
    {
        if (!isset($raw[$key]) || !is_int($raw[$key])) {
            throw new InvalidSchemeException(sprintf('%s scheme requires int "%s".', $type->value, $key));
        }
        return $raw[$key];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<int, mixed>
     */
    private static function requireArray(array $raw, string $key, SetGroupType $type): array
    {
        if (!isset($raw[$key]) || !is_array($raw[$key])) {
            throw new InvalidSchemeException(sprintf('%s scheme requires array "%s".', $type->value, $key));
        }
        return $raw[$key];
    }

    /**
     * @param array<string, mixed> $raw
     * @return int[]
     */
    private static function requireIntArray(array $raw, string $key, SetGroupType $type): array
    {
        $arr = self::requireArray($raw, $key, $type);
        foreach ($arr as $v) {
            if (!is_int($v)) {
                throw new InvalidSchemeException(sprintf('%s scheme "%s" must contain integers only.', $type->value, $key));
            }
        }
        return $arr;
    }
}
