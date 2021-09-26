<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Trick;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TrickFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 10; $i++) {
            $trick = new Trick();
            $trick->setTitle("Titre du Trick $i")
                ->setSlug("trick-$i")
                ->setDescription("Description du Trick $i")
                ->setContent("Contenu du Trick $i")
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($trick);

            $comment1 = new Comment();
            $comment1->setComment('I ate a normal rock once. It did NOT taste like bacon!');
            $comment1->setCreatedAt(new \DateTimeImmutable());
            $comment1->setTrick($trick);
            $manager->persist($comment1);

            $comment2 = new Comment();
            $comment2->setComment('I ate a normal rock once. It did NOT taste like bacon!');
            $comment2->setCreatedAt(new \DateTimeImmutable());
            $comment2->setTrick($trick);
            $manager->persist($comment2);

        }

        $manager->flush();
    }
}
