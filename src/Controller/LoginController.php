<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class LoginController extends AbstractController
{
    public function __construct(
        private UserServiceInterface $userService
    ) {
    }

    #[Route('/login', name: 'api_login', methods: 'POST')]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => $user->getUserIdentifier(),
            'apiKey' => $this->userService->obtainApiKey($user),
        ]);
    }
}
