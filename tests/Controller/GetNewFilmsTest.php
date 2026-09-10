<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DTO\SendNewFilmsDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GetNewFilmsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $container->get(EntityManagerInterface::class)
            ->createQuery('DELETE FROM App\Entity\User')
            ->execute();

        $user = new User();
        $user->setLogin('tester');
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'pa$$word')
        );
        $container->get(UserRepository::class)->save($user);
    }

    public function testRequestIsQueuedForAuthenticatedUser(): void
    {
        $this->request('POST', '/api/getNewFilms', ['email' => 'user@example.com'], $this->login());

        $this->assertResponseStatusCodeSame(202);

        // Письмо не отправляется в момент запроса — задача уходит в очередь.
        $messages = $this->client->getContainer()->get('messenger.transport.get_new_films')->get();
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(SendNewFilmsDTO::class, $messages[0]->getMessage());
    }

    public function testRequiresAuthentication(): void
    {
        $this->request('POST', '/api/getNewFilms', ['email' => 'user@example.com']);

        $this->assertResponseStatusCodeSame(401);
    }

    private function login(): string
    {
        $this->request('POST', '/api/login', ['login' => 'tester', 'password' => 'pa$$word']);

        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
    }

    private function request(string $method, string $uri, array $payload, ?string $token = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request($method, $uri, server: $server, content: json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
