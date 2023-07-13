<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Entity\User;
use App\Security\UserVoter;
use App\Services\NotifierServiceInterface;
use App\Services\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    #[IsGranted(UserVoter::VIEW_USERS)]
    public function list(): JsonResponse
    {
        $users = $this->userService->getAll();

        return $this->json($users);
    }

    #[Route('/user', name: 'create_user', methods: 'POST')]
    #[IsGranted(UserVoter::CREATE_USERS)]
    public function create(#[MapRequestPayload] UserDTO $userDTO): JsonResponse
    {
        $errors = $this->validator->validate($userDTO, null, ['create']);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->create($userDTO);
        $this->notifierService->notify($user);

        return $this->json($user);
    }

    #[Route('/user/{id}', name: 'update_user', methods: 'PUT')]
    #[IsGranted(UserVoter::EDIT_USERS, 'user')]
    public function update(#[MapRequestPayload] UserDTO $userDTO, User $user): JsonResponse
    {
        $errors = $this->validator->validate($userDTO);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->update($user, $userDTO);

        return $this->json($user);
    }

    #[Route('/user', name: 'get_profile', methods: 'GET')]
    #[IsGranted(UserVoter::VIEW_USERS)]
    public function profile(): JsonResponse
    {
        return $this->json($this->getUser());
    }

    #[Route('/user/{id}', name: 'get_user', methods: 'GET')]
    #[IsGranted(UserVoter::VIEW_USERS, 'user')]
    public function get(User $user): JsonResponse
    {
        return $this->json($user);
    }
}
