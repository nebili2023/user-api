<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ObjectRepository;

interface UserRepositoryInterface extends ObjectRepository
{
    public function add(User $entity, bool $flush = false): void;
}
