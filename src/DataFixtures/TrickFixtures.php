<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Group;
use App\Entity\Trick;
use App\Entity\TrickImage;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
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
        $faker = Faker\Factory::create('fr_FR');
        $tricks = [];
        $groups = [];
        $users = [];

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
                'description' => $faker->text(rand(30, 55))
            ],
            [
                'name' => 'Blanditiis aut', 'img' => 1,
                'description' => $faker->text(rand(30, 55))
            ],
            [
                'name' => 'Dignissimos molestiae', 'img' => 1,
                'description' => $faker->text(rand(30, 55))
            ],
            [
                'name' => 'Blanditiis aut', 'img' => 1,
                'description' => $faker->text(rand(30, 55))
            ],
            [
                'name' => 'Dignissimos molestiae', 'img' => 1,
                'description' => $faker->text(rand(30, 55))
            ],
            [
                'name' => 'Dignissimos molestiae', 'img' => 1,
                'description' => $faker->text(rand(30, 55))
            ],
        ];

        $groupsFixtures = ['Grabs', 'Rotations', 'Flips', 'Rotations désaxées', 'Slides', 'One foot', 'Old school'];


        // add users
        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setEmail($faker->email)
                ->setPassword($this->userPasswordHasher->hashPassword($user, "demo"))
                ->setFirstName($faker->firstName)
                ->setLastName($faker->lastName)
                ->setUsername($faker->firstName)
                ->setAccountConfirmed(true);
            if ($i == 0) {
                $user->setEmail('demo@gmail.com')
                    ->setRoles(['ROLE_ADMIN']);
            }

            $manager->persist($user);

            $users[] = $user;
        }


        // add groups
        for ($i = 0; $i < count($groupsFixtures); $i++) {
            $group = new Group();
            $group->setTitle($groupsFixtures[$i]);
            $manager->persist($group);

            $groups[] = $group;
        }


        // add tricks
        for ($i = 0; $i < 12; $i++) {
            $trickFixture = $tricksFixtures[$i];

            $trickFixture['slug'] = $this->slugger->slug(strtolower(str_replace('°', '', $trickFixture['name'])));

            // add trick
            $trick = new Trick();
            $trick->setTitle($trickFixture['name'])
                ->setSlug($trickFixture['slug'])
                ->setDescription($trickFixture['description'])
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());
            if ($i < count($groups)) {
                $trick->setGroup($groups[$i]);
            }
            if ($i < count($users)) {
                $trick->setUser($users[$i]);
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
                if ($j == 1) {
                    $trick->setFeaturedImage($trickImage);
                    $manager->persist($trick);
                }
            }

            $tricks[] = $trick;
        }


        // add comments
        for ($i = 0; $i < 20; $i++) {
            $comment1 = (new Comment())
                ->setComment($faker->realText(rand(40, 150)))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setTrick($tricks[0])
                ->setUser($users[$i]);
            $comment2 = (new Comment())
                ->setComment($faker->realText(rand(40, 150)))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setTrick($tricks[1])
                ->setUser($users[$i]);
            $comment3 = (new Comment())
                ->setComment($faker->realText(rand(40, 150)))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setTrick($tricks[2])
                ->setUser($users[$i]);

            $manager->persist($comment1);
            $manager->persist($comment2);
            $manager->persist($comment3);
        }


        $manager->flush();
    }
}
