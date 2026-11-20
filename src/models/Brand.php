<?php

declare(strict_types=1);

namespace App\Model;

// Marka ze słownika (tabela brands); slug służy do filtra ?brand=<slug>.
final class Brand
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
