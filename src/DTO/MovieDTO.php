<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class MovieDTO
{
    public function __construct(
        public string $title,
        public string $releaseDate,
        public float $rating,
    ) {
    }
}
