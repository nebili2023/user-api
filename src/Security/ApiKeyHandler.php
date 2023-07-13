<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiKeyHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['apiKey' => $accessToken]);

        if ($user === null) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new UserBadge($user->getUserIdentifier());
    }
}