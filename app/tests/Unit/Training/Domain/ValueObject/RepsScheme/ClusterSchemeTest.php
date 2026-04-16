<?php

declare(strict_types=1);

use App\Training\Domain\ValueObject\RepsScheme\ClusterScheme;

it('captures subSets, reps per subset and intra-rest seconds', function (): void {
    $scheme = new ClusterScheme(subSets: 4, reps: 4, intraRestSeconds: 20);
    expect($scheme->subSets)->toBe(4)
        ->and($scheme->reps)->toBe(4)
        ->and($scheme->intraRestSeconds)->toBe(20);
});

it('allows a 5x3 cluster with 15s micro-rest', function (): void {
    $scheme = new ClusterScheme(subSets: 5, reps: 3, intraRestSeconds: 15);
    expect($scheme->subSets)->toBe(5)->and($scheme->reps)->toBe(3);
});

it('rejects a cluster with fewer than 2 subSets', function (): void {
    new ClusterScheme(subSets: 1, reps: 5, intraRestSeconds: 20);
})->throws(InvalidArgumentException::class);

it('rejects negative intra-rest seconds', function (): void {
    new ClusterScheme(subSets: 4, reps: 4, intraRestSeconds: -1);
})->throws(InvalidArgumentException::class);
