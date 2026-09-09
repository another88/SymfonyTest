<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Тестовая БД переиспользуется между запусками, поэтому чистим таблицу.
        $entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        $user = new User();
        $user->setLogin('tester');
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'pa$$word')
        );

        $container->get(UserRepository::class)->save($user);
    }

    public function testLoginReturnsToken(): void
    {
        $this->assertNotEmpty($this->login('tester', 'pa$$word'));
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $this->request('POST', '/api/login', ['login' => 'tester', 'password' => 'nope']);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointReturnsCurrentUser(): void
    {
        $token = $this->login('tester', 'pa$$word');

        $this->client->request('GET', '/api/me', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('tester', $this->decodeResponse()['login']);
        $this->assertSame(['ROLE_USER'], $this->decodeResponse()['roles']);
    }

    public function testProtectedEndpointRequiresToken(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointRejectsInvalidToken(): void
    {
        $this->client->request('GET', '/api/me', server: [
            'HTTP_AUTHORIZATION' => 'Bearer not.a.token',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    private function login(string $login, string $password): string
    {
        $this->request('POST', '/api/login', ['login' => $login, 'password' => $password]);

        $this->assertResponseIsSuccessful();

        return $this->decodeResponse()['token'];
    }

    private function request(string $method, string $uri, array $payload): void
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    private function decodeResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
