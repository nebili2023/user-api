<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Entity\User;
use App\Exception\DataValidationException;
use App\Repository\UserRepositoryInterface;
use App\Services\NotifierServiceInterface;
use App\Services\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    public function __construct(
        public UserServiceInterface $userService,
        public NotifierServiceInterface $notifierService,
        public ValidatorInterface $validator
    ) {
    }

    #[Route('/users', name: 'list_users', methods: 'GET')]
    public function list(): JsonResponse
    {
        $users = $this->userService->getAll();

        return $this->json($users);
    }

    #[Route('/user', name: 'create_user', methods: 'POST')]
    public function create(#[MapRequestPayload] UserDTO $userDTO): JsonResponse
    {
        $errors = $this->validator->validate($userDTO, null, ['create']);
        if (count($errors) > 0) {
            throw new ($errors);
        }

        $user = $this->userService->create($userDTO);
        $this->notifierService->notify($user);

        return $this->json($user);
    }

    #[Route('/user/{id}', name: 'update_user', methods: 'PUT')]
    public function update(#[MapRequestPayload] UserDTO $userDTO, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $userDTO);

        return $this->json($user);
    }

    #[Route('/user/{id}', name: 'get_user', methods: 'GET')]
    public function get(User $user): JsonResponse
    {
        return $this->json($user);
    }
}
