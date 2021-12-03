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
        $tricks = [];

        $tricksFixtures = [
            [
                'name' => 'Mute grab', 'img' => 4,
                'description' => 'Saisie de la carre frontside de la planche entre les deux pieds avec la main avant'
            ],
            [
                'name' => "Rotation 360°", 'img' => 4,
                'description' => 'Rotation horizontale de 360°'
            ],
            [
                'name' => 'Front Flip', 'img' => 4,
                'description' => 'Rotation verticale en avant'
            ],
            [
                'name' => 'Corkscrew', 'img' => 2,
                'description' => 'Rotations désaxées Corkscrew'
            ],
            [
                'name' => 'Nose Slide', 'img' => 2,
                'description' => "Glisse sur une barre de slide avec l'avant de la planche"
            ],
            [
                'name' => 'One Foot', 'img' => 2,
                'description' => 'Figure avec un pied décroché de la fixation'
            ],
            [
                'name' => 'Japan Air', 'img' => 1,
                'description' => ''
            ],
            [
                'name' => 'Trick 8', 'img' => 1,
                'description' => ''
            ],
            [
                'name' => 'Trick 9', 'img' => 1,
                'description' => ''
            ],
            [
                'name' => 'Trick 10', 'img' => 1,
                'description' => ''
            ],
            [
                'name' => 'Trick 11', 'img' => 1,
                'description' => ''
            ],
            [
                'name' => 'Trick 12', 'img' => 1,
                'description' => ''
            ]
        ];

        $groups = [];
        $groupsFixtures = ['Grabs', 'Rotations', 'Flips', 'Rotations désaxées', 'Slides', 'One foot', 'Old school'];

        // add groups and linked tricks
        for ($i = 0; $i < count($groupsFixtures); $i++) {
            $group = new Group();
            $group->setTitle($groupsFixtures[$i]);
            $manager->persist($group);

            $groups[] = $group;
        }


        // add tricks
        for ($i = 0; $i < 10; $i++) {
            $trickFixture = $tricksFixtures[$i];

            $trickFixture['slug'] = $this->slugger->slug(strtolower(str_replace('°', '', $trickFixture['name'])));

            // add trick
            $trick = new Trick();
            $trick->setTitle($trickFixture['name'])
                ->setSlug($trickFixture['slug'])
                ->setDescription($trickFixture['description'])
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            if($i < count($groups)) {
                $trick->setGroup($groups[$i]);
            }
            $manager->persist($trick);

            // add trick images
            for ($j = 1; $j <= $trickFixture['img']; $j++) {
                $trickImage = (new TrickImage())
                    ->setTitle("Image $j")
                    ->setFilename('fixtures/snowboard-' . $trickFixture['slug'] . "-$j.jpg")
                    ->setTrick($trick);
                $manager->persist($trickImage);

                // make first image as featured
                if($j == 1) {
                    $trick->setFeaturedImage($trickImage);
                    $manager->persist($trick);
                }
            }

            $tricks[] = $trick;
        }

        $usersFixtures = [
            ['email' => 'eevi.wirta@example.com'],
            ['email' => 'sanni.lauri@example.com'],
            ['email' => 'phil.banks@example.com'],
            ['email' => 'joana.mader@example.com'],
        ];


        // add users and add linked comments
        for ($i = 0; $i < 4; $i++) {
            $user = new User();
            $user->setEmail($usersFixtures[$i]['email'])
                ->setPassword($this->userPasswordHasher->hashPassword($user, "pass"))
                ->setFirstName("FirstName$i")
                ->setLastName("LastName$i")
                ->setUsername("Username$i");

            $comment1 = (new Comment())
                ->setComment('I ate a normal rock once. It did NOT taste like bacon!')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setTrick($tricks[$i])
                ->setUser($user);
            $comment2 = (new Comment())
                ->setComment('I ate a normal rock once. It did NOT taste like bacon!')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setTrick($tricks[$i])
                ->setUser($user);

            $manager->persist($user);
            $manager->persist($comment1);
            $manager->persist($comment2);
        }


        $manager->flush();
    }
}
