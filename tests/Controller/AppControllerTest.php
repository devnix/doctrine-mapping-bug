<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\DoctrineTestCaseTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * @phpstan-type ApiUser array{id: ?int, alias: string, username: string, password: string}
 * @phpstan-type ApiApp array{id: string, users: array<ApiUser>}
 */
class AppControllerTest extends WebTestCase
{
    use DoctrineTestCaseTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->clearDatabase();
    }

    /**
     * Query the registered apps to our REST API
     *
     * @phpstan-return array<ApiApp>
     */
    private function getApps(): array
    {
        $this->client->request('GET', '/apps');

        $this->assertJsonResponse($this->client->getResponse());

        return $this->jsonDecode(
            (string) $this->client->getResponse()->getContent()
        );
    }

    /**
     * Query the registered app to our REST API
     *
     * @phpstan-return ApiApp
     */
    private function getApp(string $id): array
    {
        $this->client->request('GET', '/apps/'.$id);

        $this->assertJsonResponse($this->client->getResponse());

        return $this->jsonDecode(
            (string) $this->client->getResponse()->getContent()
        );
    }

    /**
     * Create an app through our REST API
     *
     * @return array{id: string}
     */
    private function createApp(): array
    {
        $this->client->request('POST', '/apps');

        $this->assertJsonResponse($this->client->getResponse());

        return $this->jsonDecode(
            (string) $this->client->getResponse()->getContent()
        );
    }

    /**
     * Create a new user for a given
     */
    private function createUser(string $id, string $alias, string $username, string $password): void
    {
        $this->client->request(
            'POST',
            sprintf('/apps/%s', $id),
            [
                'alias' => $alias,
                'username' => $username,
                'password' => $password,
            ]
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJsonResponse($this->client->getResponse());
    }

    /**
     * Return the list of users for the given App id
     *
     * @phpstan-return ApiUser
     */
    private function getUser(string $id, string $username): array
    {
        $this->client->request(
            'GET',
            sprintf('/apps/%s/%s', $id, $username)
        );
        return $this->jsonDecode(
            (string) $this->client->getResponse()->getContent()
        );
    }

    private function login(string $id, string $username, string $password): bool
    {
        $this->client->request(
            'GET',
            sprintf('/apps/%s/login', $id),
            [
                'username' => $username,
                'password' => $password
            ]
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertJsonResponse($this->client->getResponse());
        return $this->jsonDecode(
            (string) $this->client->getResponse()->getContent()
        );
    }

    private function changePassword(string $id, string $username, string $newPassword): void
    {
        $this->client->request(
            'PUT',
            sprintf('/apps/%s/%s/changePassword', $id, $username),
            [
                'newPassword' => $newPassword,
            ]
        );
    }

    /**
     * @test
     */
    public function it_should_return_empty_list_when_no_apps_are_still_created(): void
    {
        $apps = $this->getApps();

        $this->assertEquals([], $apps);
    }

    /**
     * @test
     */
    public function it_should_return_200_and_a_uuid_after_creating_an_app(): void
    {
        $createdAppResponse = $this->createApp();

        $this->assertArrayHasKey('id', $createdAppResponse);
        $this->assertTrue(Uuid::isValid($createdAppResponse['id']));
    }

    /**
     * @test
     */
    public function it_should_list_the_created_applications(): void
    {
        $createdAppResponse = $this->createApp();
        $apps = $this->getApps();
        $this->assertCount(1, $apps);
        $this->assertSame($createdAppResponse['id'], $apps[0]['id']);

        $this->createApp();
        $apps = $this->getApps();
        $this->assertCount(2, $apps);
    }

    /**
     * @test
     */
    public function it_should_retrieve_the_created_applications_individually(): void
    {
        $createdAppResponse = $this->createApp();
        $app = $this->getApp($createdAppResponse['id']);
        $this->assertSame($createdAppResponse['id'], $app['id']);
        $this->assertCount(0, $app['users']);
    }

    /**
     * @test
     */
    public function it_should_create_and_list_users(): void
    {
        $createdAppResponse = $this->createApp();
        $app = $this->getApp($createdAppResponse['id']);
        $this->assertCount(0, $app['users']);

        $this->createUser(
            $app['id'],
            'A normal user',
            'bob',
            'test1234'
        );

        $app = $this->getApp($createdAppResponse['id']);
        $this->assertCount(1, $app['users']);
        $this->assertSame('A normal user', $app['users'][0]['alias']);
        $this->assertSame('bob', $app['users'][0]['username']);
        $this->assertSame('test1234', $app['users'][0]['password']);

        $user = $this->getUser($createdAppResponse['id'], 'bob');
        $this->assertSame('A normal user', $user['alias']);
        $this->assertSame('bob', $user['username']);
        $this->assertSame('test1234', $user['password']);
    }

    /**
     * @test
     */
    public function it_should_login_if_credentials_are_valid(): void
    {
        $createdAppResponse = $this->createApp();
        $app = $this->getApp($createdAppResponse['id']);
        $this->createUser(
            $app['id'],
            'A normal user',
            'bob',
            'test1234'
        );

        $this->login($app['id'], 'bob', 'test1234');
    }

    /**
     * @test
     */
    public function it_should_not_login_if_credentials_are_wrong(): void
    {
        $createdAppResponse = $this->createApp();
        $app = $this->getApp($createdAppResponse['id']);
        $this->createUser(
            $app['id'],
            'A normal user',
            'bob',
            'test1234'
        );

        $this->login($app['id'], 'bob', 'badpassword');
    }

    /**
     * @test
     */
    public function it_should_change_password_successfully(): void
    {
        $createdAppResponse = $this->createApp();
        $app = $this->getApp($createdAppResponse['id']);
        $this->createUser(
            $app['id'],
            'A normal user',
            'bob',
            'test1234'
        );

        $this->changePassword($app['id'], 'bob', 'newPassword!');

        $user = $this->getUser($app['id'], 'bob');
        $this->assertSame('newPassword!', $user['password']);
    }

    private function assertJsonResponse(Response $response): void
    {
        $this->assertTrue(
            $response->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'Failed asserting that response Content-Type is application/json'
        );
    }

    private function getDecoder(): DecoderInterface
    {
        $decoder = self::getContainer()->get(DecoderInterface::class);
        assert($decoder instanceof DecoderInterface);

        return $decoder;
    }

    private function jsonDecode(string $json): mixed
    {
        return $this->getDecoder()->decode($json, 'json');
    }
}
