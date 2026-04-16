<?php

declare(strict_types=1);

use App\Training\Domain\Exception\InvalidSchemeException;
use App\Training\Domain\ValueObject\RepsScheme\AmrapScheme;
use App\Training\Domain\ValueObject\RepsScheme\ClusterScheme;
use App\Training\Domain\ValueObject\RepsScheme\DescendingScheme;
use App\Training\Domain\ValueObject\RepsScheme\PapScheme;
use App\Training\Domain\ValueObject\RepsScheme\PartialPlusFullScheme;
use App\Training\Domain\ValueObject\RepsScheme\PoliquinTrisetScheme;
use App\Training\Domain\ValueObject\RepsScheme\RepsSchemeFactory;
use App\Training\Domain\ValueObject\RepsScheme\RestPauseScheme;
use App\Training\Domain\ValueObject\RepsScheme\ScdScheme;
use App\Training\Domain\ValueObject\RepsScheme\StraightScheme;
use App\Training\Domain\ValueObject\RepsScheme\SupersetScheme;
use App\Training\Enum\SetGroupType;

describe('valid shapes produce the matching VO', function (): void {
    it('maps STRAIGHT', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::STRAIGHT, ['reps' => '9-13']))
            ->toBeInstanceOf(StraightScheme::class);
    });

    it('maps AMRAP (spec scenario "Shape válido produce VO correcto")', function (): void {
        $scheme = RepsSchemeFactory::fromArray(SetGroupType::AMRAP, ['target' => 8, 'min' => 7]);
        expect($scheme)->toBeInstanceOf(AmrapScheme::class)
            ->and($scheme->target)->toBe(8)
            ->and($scheme->min)->toBe(7);
    });

    it('maps DESCENDING', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::DESCENDING, ['drops' => [6, 10]]))
            ->toBeInstanceOf(DescendingScheme::class);
    });

    it('maps CLUSTER', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::CLUSTER, [
            'subSets' => 4, 'reps' => 4, 'intraRestSeconds' => 20,
        ]))->toBeInstanceOf(ClusterScheme::class);
    });

    it('maps REST_PAUSE', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::REST_PAUSE, [
            'initial' => 8, 'microSets' => [[3, 4]], 'intraRestSeconds' => 25,
        ]))->toBeInstanceOf(RestPauseScheme::class);
    });

    it('maps PAP', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::PAP, [
            'heavySingle' => 1, 'workSets' => 3, 'workReps' => '6-8',
        ]))->toBeInstanceOf(PapScheme::class);
    });

    it('maps SUPERSET', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::SUPERSET, ['pairedWith' => 'A2']))
            ->toBeInstanceOf(SupersetScheme::class);
    });

    it('maps POLIQUIN_TRISET', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::POLIQUIN_TRISET, [
            'a' => 6, 'b' => 12, 'c' => 25,
        ]))->toBeInstanceOf(PoliquinTrisetScheme::class);
    });

    it('maps PARTIAL_PLUS_FULL', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::PARTIAL_PLUS_FULL, [
            'partialReps' => 8, 'fullReps' => 4,
        ]))->toBeInstanceOf(PartialPlusFullScheme::class);
    });

    it('maps SCD', function (): void {
        expect(RepsSchemeFactory::fromArray(SetGroupType::SCD, [
            'descending' => [5, 4, 3], 'holdSeconds' => [1, 2, 3],
        ]))->toBeInstanceOf(ScdScheme::class);
    });
});

describe('incongruent shape is rejected (spec scenario)', function (): void {
    it('AMRAP with STRAIGHT shape throws InvalidSchemeException', function (): void {
        RepsSchemeFactory::fromArray(SetGroupType::AMRAP, ['reps' => '6-8']);
    })->throws(InvalidSchemeException::class);

    it('STRAIGHT missing reps key throws', function (): void {
        RepsSchemeFactory::fromArray(SetGroupType::STRAIGHT, []);
    })->throws(InvalidSchemeException::class);

    it('CLUSTER missing subSets throws', function (): void {
        RepsSchemeFactory::fromArray(SetGroupType::CLUSTER, ['reps' => 4, 'intraRestSeconds' => 20]);
    })->throws(InvalidSchemeException::class);

    it('DESCENDING with drops of wrong type throws', function (): void {
        RepsSchemeFactory::fromArray(SetGroupType::DESCENDING, ['drops' => 'not-an-array']);
    })->throws(InvalidSchemeException::class);
});
