<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\MovieDTO;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbClient
{
    public function __construct(private readonly HttpClientInterface $tmdbClient)
    {
    }

    /**
     * Фильмы, попавшие в тренды за неделю.
     *
     * @return MovieDTO[]
     */
    public function getTrendingMovies(int $limit = 10): array
    {
        $results = $this->tmdbClient->request('GET', 'trending/movie/week')->toArray()['results'];

        return array_map(
            static fn (array $movie) => new MovieDTO(
                $movie['title'],
                $movie['release_date'],
                $movie['vote_average'],
            ),
            array_slice($results, 0, $limit)
        );
    }
}
