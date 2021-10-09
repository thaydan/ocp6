<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TrickFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $j = 1;

        for ($i = 1; $i <= 10; $i++) {
            $trick = new Trick();
            $trick->setTitle("Titre du Trick $i")
                ->setSlug("trick-$i")
                ->setDescription("Description du Trick $i")
                ->setContent("Contenu du Trick $i")
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($trick);

            for ($j = $j; $j <= 4; $j++) {
                $user = new User();
                var_dump("email$j@gmail.com");
                $user->setEmail("email$j@gmail.com")
                    ->setPassword($this->userPasswordHasher->hashPassword($user, "pass$j"))
                    ->setFirstName("FirstName$j")
                    ->setLastName("LastName$j")
                    ->setUsername("Username$j");

                $comment1 = (new Comment())
                    ->setComment('I ate a normal rock once. It did NOT taste like bacon!')
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setTrick($trick)
                    ->setUser($user);
                $comment2 = (new Comment())
                    ->setComment('I ate a normal rock once. It did NOT taste like bacon!')
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setTrick($trick)
                    ->setUser($user);

                $manager->persist($user);
                $manager->persist($comment1);
                $manager->persist($comment2);
            }
        }

        $manager->flush();
    }
}
