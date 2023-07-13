<?php

namespace App\Services;

use App\DTO\UserDTO;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

interface UserServiceInterface
{
    public function obtainApiKey(User $user): string;

    public function getAll(): ArrayCollection;

    public function create(UserDTO $userDTO): User;

    public function update(User $user, UserDTO $userDTO): User;
}