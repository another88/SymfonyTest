<?php

declare(strict_types=1);

namespace App\MessageHandler\SendNewFilms;

use App\DTO\MovieDTO;
use App\DTO\SendNewFilmsDTO;
use App\Service\TmdbClient;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class Handler
{
    public function __construct(
        private readonly TmdbClient $tmdbClient,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendNewFilmsDTO $message): void
    {
        $lines = array_map(
            static fn (MovieDTO $movie) => sprintf(
                '%s (%s), рейтинг %s',
                $movie->title,
                $movie->releaseDate,
                round($movie->rating, 1)
            ),
            $this->tmdbClient->getTrendingMovies()
        );

        $this->mailer->send(
            (new Email())
                ->from('noreply@symfony.local')
                ->to($message->email)
                ->subject('Новинки недели')
                ->text(implode("\n", $lines))
        );
    }
}
