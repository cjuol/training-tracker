<?php

declare(strict_types=1);

use App\Training\Service\ExerciseSlugifier;

it('lowercases and kebab-cases a plain exercise name', function (): void {
    expect((new ExerciseSlugifier())->slugify('Press Militar Maquina'))
        ->toBe('press-militar-maquina');
});

it('removes Spanish diacritics canonically', function (): void {
    $slug = (new ExerciseSlugifier())->slugify('Flexión profunda con máquina');
    expect($slug)->toBe('flexion-profunda-con-maquina');
});

it('handles eñe and other Spanish-specific letters', function (): void {
    expect((new ExerciseSlugifier())->slugify('Patada española'))->toBe('patada-espanola');
});

it('collapses duplicate spaces and trims edges', function (): void {
    expect((new ExerciseSlugifier())->slugify('  Curl   Scott  '))->toBe('curl-scott');
});

it('strips punctuation that is not a separator', function (): void {
    expect((new ExerciseSlugifier())->slugify("Press de 'banca' (plano)"))->toBe('press-de-banca-plano');
});

it('is idempotent over already-canonical slugs', function (): void {
    $slugifier = new ExerciseSlugifier();
    expect($slugifier->slugify('press-militar-maquina'))->toBe('press-militar-maquina');
});

it('rejects an empty name', function (): void {
    (new ExerciseSlugifier())->slugify('');
})->throws(InvalidArgumentException::class);
