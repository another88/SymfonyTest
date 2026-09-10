<?php

declare(strict_types=1);

namespace App\Controller\Api\GetNewFilms\v1;

use App\DTO\SendNewFilmsDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class Controller extends AbstractController
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    // Отвечаем сразу, а список новинок собирает и отправляет воркер.
    #[Route(path: '/api/getNewFilms', methods: ['POST'])]
    public function getNewFilmsAction(#[MapRequestPayload] SendNewFilmsDTO $dto): Response
    {
        $this->messageBus->dispatch($dto);

        return new JsonResponse(
            ['message' => 'Вам будет отправлено письмо со списком новинок'],
            Response::HTTP_ACCEPTED
        );
    }
}
