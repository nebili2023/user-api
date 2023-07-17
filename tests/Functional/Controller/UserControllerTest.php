<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\NotifierServiceInterface;
use App\Services\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserControllerTest extends WebTestCase
{
    private UserServiceInterface|\PHPUnit\Framework\MockObject\MockObject $userServiceMock;
    private NotifierServiceInterface|\PHPUnit\Framework\MockObject\MockObject $notifierServiceMock;

    private \Faker\Generator $faker;

    public function setUp(): void
    {
        $this->userServiceMock = $this->createMock(UserServiceInterface::class);
        $this->notifierServiceMock = $this->createMock(NotifierServiceInterface::class);

        $this->faker = \Faker\Factory::create();
    }

    public function testListAsAnonymous(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = static::createClient();
        $client->catchExceptions(false);

        $client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListAsNonAdmin(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = static::createClient();
        $client->catchExceptions(false);

        $user = $this->retrieveUser('user.test.one@mailtest.io');
        $client->loginUser($user);

        $client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListAsAdmin(): void
    {
        $client = static::createClient();

        $user = $this->retrieveUser('user.test.admin@mailtest.io');
        $client->loginUser($user);

        $client->request('GET', '/users');

        $response = $client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testCreateAsNonAdmin(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = static::createClient();
        $client->catchExceptions(false);

        $user = $this->retrieveUser('user.test.one@mailtest.io');
        $client->loginUser($user);

        $client->request('POST', '/user');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateAsAdminWithValidData(): void
    {
        $client = static::createClient();

        $user = $this->retrieveUser('user.test.admin@mailtest.io');
        $client->loginUser($user);

        $expected = $this->generateUser();

        $this->userServiceMock->expects($this->once())->method('create')->willReturn($expected);
        $this->notifierServiceMock->expects($this->once())->method('notify');

        static::getContainer()->set(UserServiceInterface::class, $this->userServiceMock);
        static::getContainer()->set(NotifierServiceInterface::class, $this->notifierServiceMock);

        $client->request('POST', '/user', [
            'email' => $expected->getEmail(),
            'roles' => $expected->getRoles(),
        ]);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
        $this->assertEquals($expected->getEmail(), $responseData['email']);
        $this->assertEquals($expected->getRoles(), $responseData['roles']);
    }

    public function testCreateAsAdminWithInvalidData(): void
    {
        $client = static::createClient();

        $user = $this->retrieveUser('user.test.admin@mailtest.io');
        $client->loginUser($user);

        $this->userServiceMock->expects($this->never())->method('create');
        $this->notifierServiceMock->expects($this->never())->method('notify');

        static::getContainer()->set(UserServiceInterface::class, $this->userServiceMock);
        static::getContainer()->set(NotifierServiceInterface::class, $this->notifierServiceMock);

        $client->request('POST', '/user', [
            'email' => '',
            'roles' => [],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateAsNonAdminOwnProfile(): void
    {
        $client = static::createClient();

        $user = $this->retrieveUser('user.test.two@mailtest.io');
        $client->loginUser($user);

        $expected = clone $user;
        $expected->setName($this->faker->name());

        $this->userServiceMock->expects($this->once())->method('update')->willReturn($expected);

        static::getContainer()->set(UserServiceInterface::class, $this->userServiceMock);

        $client->request('PUT', '/user/' . $user->getId(), [
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'name' => $expected->getName()
        ]);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
        $this->assertEquals($expected->getName(), $responseData['name']);
    }

    public function testUpdateAnyAsNonAdmin(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = static::createClient();
        $client->catchExceptions(false);

        $user = $this->retrieveUser('user.test.one@mailtest.io');
        $client->loginUser($user);

        $updating = $this->retrieveUser('user.test.two@mailtest.io');

        $expected = clone $updating;
        $expected->setName($this->faker->name());

        $this->userServiceMock->expects($this->never())->method('update');

        static::getContainer()->set(UserServiceInterface::class, $this->userServiceMock);

        $client->request('PUT', '/user/' . $expected->getId(), [
            'email' => $expected->getEmail(),
            'roles' => $expected->getRoles(),
            'name' => $expected->getName(),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateAnyAsAdmin(): void
    {
        $client = static::createClient();

        $user = $this->retrieveUser('user.test.admin@mailtest.io');
        $client->loginUser($user);

        $updating = $this->retrieveUser('user.test.two@mailtest.io');

        $expected = clone $updating;
        $expected->setName($this->faker->name());

        $this->userServiceMock->expects($this->once())->method('update')->willReturn($expected);

        static::getContainer()->set(UserServiceInterface::class, $this->userServiceMock);

        $client->request('PUT', '/user/' . $expected->getId(), [
            'email' => $expected->getEmail(),
            'roles' => $expected->getRoles(),
            'name' => $expected->getName(),
        ]);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
        $this->assertEquals($expected->getEmail(), $responseData['email']);
        $this->assertEquals($expected->getRoles(), $responseData['roles']);
        $this->assertEquals($expected->getName(), $responseData['name']);
    }

    public function testUpdateAnyAsAdminWithInvalidData(): void
    {
        $this->expectException(HttpException::class);

        $client = static::createClient();
        $client->catchExceptions(false);

        $user = $this->retrieveUser('user.test.admin@mailtest.io');
        $client->loginUser($user);

        $updating = $this->retrieveUser('user.test.two@mailtest.io');

        $this->userServiceMock->expects($this->never())->method('update');

        static::getContainer()->set(UserServiceInterface::class, $this->userServiceMock);

        $client->request('PUT', '/user/' . $updating->getId(), [
            'email' => $this->faker->word(),
            'roles' => $updating->getRoles(),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testViewAnyAsNonAdmin(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = static::createClient();
        $client->catchExceptions(false);

        $user = $this->retrieveUser('user.test.one@mailtest.io');
        $client->loginUser($user);

        $viewing = $this->retrieveUser('user.test.two@mailtest.io');

        $client->request('GET', '/user/' . $viewing->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testViewOwnAsNonAdmin(): void
    {
        $client = static::createClient();

        $user = $this->retrieveUser('user.test.one@mailtest.io');
        $client->loginUser($user);

        $client->request('GET', '/user/' . $user->getId());

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
        $this->assertEquals($user->getEmail(), $responseData['email']);
    }

    public function testViewAsAdmin(): void
    {
        $client = static::createClient();

        $user = $this->retrieveUser('user.test.admin@mailtest.io');
        $client->loginUser($user);

        $viewing = $this->retrieveUser('user.test.two@mailtest.io');

        $client->request('GET', '/user/' . $viewing->getId());

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
        $this->assertEquals($viewing->getEmail(), $responseData['email']);
    }

    private function retrieveUser(string $email): User
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        return $userRepository->findOneByEmail($email);
    }

    private function generateUser(): User
    {
        $user = new User();
        $user->setEmail($this->faker->email());
        $user->setRoles([User::ROLE_USER]);

        return $user;
    }
}
