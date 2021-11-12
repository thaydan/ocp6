<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Group;
use App\Entity\Trick;
use App\Entity\TrickImage;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class TrickFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;
    private SluggerInterface $slugger;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, SluggerInterface $slugger)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager)
    {
        $j = 1;
        $tricks = [];

        $trickNames = ['Mute grab', 'Rotation 360°', 'Front Flip', 'Corkscrew', 'Nose Slide', 'One Foot', 'Japan Air', 'Trick 8', 'Trick 9', 'Trick 10'];

        for ($i = 1; $i <= 10; $i++) {
            //var_dump($trickImage);

            $trick = new Trick();
            $trick->setTitle($trickNames[$i-1])
                ->setSlug("trick-$i")
                ->setDescription("Description du Trick $i")
                ->setContent("Contenu du Trick $i")
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($trick);

            $trickImage = (new TrickImage())
                ->setTitle("Image $i")
                ->setFilename('fixtures/snowboard-' . strtolower($this->slugger->slug(str_replace('°', '', $trickNames[$i-1]))) . '.jpg')
                ->setTrick($trick);
            $manager->persist($trickImage);

            $trick->setFeaturedImage($trickImage);
            $manager->persist($trick);

            for ($j = $j; $j <= 4; $j++) {
                $user = new User();
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

            $tricks[] = $trick;
        }


        $groups = ['Grabs', 'Rotations', 'Flips', 'Rotations désaxées', 'Slides', 'One foot', 'Old school'];
        for ($i = 0; $i < count($groups); $i++) {
            $group = new Group();
            $group->setTitle($groups[$i])->addTrick($tricks[$i]);
            $manager->persist($group);
        }

        $manager->flush();
    }
}
