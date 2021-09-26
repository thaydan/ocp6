<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 4; $i++) {
            $user = new User();

            $user->setEmail("email$i@gmail.com")
                ->setPassword($this->userPasswordHasher->hashPassword($user, "pass$i"))
                ->setFirstName("FirstName$i")
                ->setLastName("LastName$i")
                ->setUsername("Username$i");

            $manager->persist($user);
        }

        $manager->flush();
    }
}
