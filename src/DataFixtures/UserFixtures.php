<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user.test.admin@mailtest.io');
        $user->setRoles([User::ROLE_ADMIN, User::ROLE_USER]);
        $user->setApiKey('user.test.admin.api_key');
        $manager->persist($user);

        $user = new User();
        $user->setEmail('user.test.one@mailtest.io');
        $user->setRoles([User::ROLE_USER]);
        $user->setApiKey('user.test.one.api_key');
        $manager->persist($user);

        $user = new User();
        $user->setEmail('user.test.two@mailtest.io');
        $user->setRoles([User::ROLE_USER]);
        $manager->persist($user);

        $manager->flush();
    }
}
