<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const CREATE_USERS = 'create_users';
    const EDIT_USERS = 'edit_users';
    const VIEW_USERS = 'view_users';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CREATE_USERS, self::EDIT_USERS, self::VIEW_USERS])) {
            return false;
        }

        if ($subject !== null && !$subject instanceof User) {
            return false;
        }

        return true;

    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW_USERS => $this->canView($user, $subject),
            self::CREATE_USERS => $this->canCreate($user),
            self::EDIT_USERS => $this->canEdit($user, $subject)
        };
    }

    private function canView(User $user, ?User $subject): bool
    {
        return $user->isAdmin() || is_null($subject) || $subject === $user;
    }

    private function canCreate(User $user): bool
    {
        return $user->isAdmin();
    }

    private function canEdit(User $user, User $subject): bool
    {
        return $user->isAdmin() || ($subject === $user);
    }
}