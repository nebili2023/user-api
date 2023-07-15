<?php

namespace App\Tests\Unit\Services;

use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\Services\UserService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class UserServiceTest extends TestCase
{
    private UserService $service;

    private MockObject|UserRepositoryInterface $userRepositoryMock;

    private MockObject|Security $securityMock;

    private \Faker\Generator $faker;

    public function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->securityMock = $this->createMock(Security::class);

        $this->service = new UserService($this->userRepositoryMock, $this->securityMock);

        $this->faker = \Faker\Factory::create();

    }

    public function testCreate()
    {
        $dto = $this->generateUserDto();

        $this->userRepositoryMock->expects($this->once())->method('add');

        $result = $this->service->create($dto);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($dto->getEmail(), $result->getEmail());
        $this->assertEquals(array_merge($dto->getRoles(), [User::ROLE_USER]), $result->getRoles());
        $this->assertEquals($dto->getName(), $result->getName());
        $this->assertEquals($dto->getPhoneNumber(), $result->getPhoneNumber());
    }

    public function testUpdateAsAdmin()
    {
        $dto = $this->generateUserDto();
        $loggedInUser = $this->generateUser(true);
        $editingUser = $this->generateUser();

        $this->securityMock
            ->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($loggedInUser);

        $this->userRepositoryMock->expects($this->once())->method('add');

        $result = $this->service->update($editingUser, $dto);

        $this->assertEquals($editingUser->getEmail(), $result->getEmail());
        $this->assertNotEquals($dto->getEmail(), $result->getEmail());
        $this->assertEquals(array_merge($dto->getRoles(), [User::ROLE_USER]), $result->getRoles());
    }

    public function testUpdateAsNonAdmin()
    {
        $dto = $this->generateUserDto();
        $user = $this->generateUser();

        $this->securityMock
            ->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $this->userRepositoryMock->expects($this->once())->method('add');

        $result = $this->service->update($user, $dto);

        $this->assertEquals($user->getEmail(), $result->getEmail());
        $this->assertNotEquals($dto->getEmail(), $result->getEmail());
        $this->assertEquals($user->getRoles(), $result->getRoles());
        $this->assertNotEquals(array_merge($dto->getRoles(), [User::ROLE_USER]), $result->getRoles());

    }

    private function generateUserDto(): UserDTO
    {
        $dto = new UserDTO();
        $dto->setEmail($this->faker->email());
        $dto->setRoles([$this->faker->word()]);
        $dto->setName($this->faker->name());
        $dto->setPhoneNumber($this->faker->phoneNumber());

        return $dto;
    }

    private function generateUser(bool $isAdmin = false): User
    {
        $roles = [$this->faker->word(), User::ROLE_USER];

        if ($isAdmin) {
            $roles = array_merge($roles, [User::ROLE_ADMIN]);
        }

        $user = new User();
        $user->setEmail($this->faker->email());
        $user->setRoles($roles);

        return $user;
    }
}
