<?php

declare(strict_types=1);

namespace App\Training\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class ExerciseSlugifier
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger('es');
    }

    public function slugify(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Exercise name cannot be empty.');
        }
        return $this->slugger->slug($trimmed)->lower()->toString();
    }
}
