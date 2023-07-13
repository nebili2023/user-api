<?php

namespace App\Services;

use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserService implements UserServiceInterface
{
    public function __construct(
        public UserRepositoryInterface $userRepository,
        public TokenStorageInterface $tokenStorage
    ) {
    }

    public function obtainApiKey(User $user): string
    {
        $apiKey = sha1(microtime());
        $user->setApiKey($apiKey);
        $this->userRepository->add($user, true);

        return $apiKey;
    }

    public function getAll(): ArrayCollection
    {
        return $this->userRepository->findAll();
    }

    public function create(UserDTO $userDTO): User
    {
        $user = new User();

        $user->setEmail($userDTO->getEmail());
        $user->setName($userDTO->getName());
        $user->setPhoneNumber($userDTO->getPhoneNumber());
        $user->setRoles($userDTO->getRoles());

        $this->userRepository->add($user, true);

        return $user;
    }

    public function update(User $user, UserDTO $userDTO): User
    {
        $user->setName($userDTO->getName());
        $user->setPhoneNumber($userDTO->getPhoneNumber());

        $authContextUser = $this->tokenStorage->getToken()->getUser();

        if ( !is_null($authContextUser) && $authContextUser->isAdmin()) {
            $user->setRoles($userDTO->getRoles());
        }

        $this->userRepository->add($user, true);

        return $user;
    }
}