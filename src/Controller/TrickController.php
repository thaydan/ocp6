<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\TrickType;
use App\Service\SpamChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrickController extends AbstractController
{
    /**
     * @Route("/trick/new", name="trick_new")
     * @Route("/trick/{slug}/edit", name="trick_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(Request $request, EntityManagerInterface $manager, Trick $trick = null, $slug = null): Response
    {
        $routeName = $request->attributes->get('_route');
        if ($routeName == 'trick_edit' && !$trick) {
            return $this->redirectToRoute('home');
        }

        if (!$trick) {
            $trick = new Trick();
        }

        $originalImages = new ArrayCollection();
        $originalVideos = new ArrayCollection();

        // Create an ArrayCollection of the current Tag objects in the database
        foreach ($trick->getImages() as $image) {
            $originalImages->add($image);
        }
        foreach ($trick->getVideos() as $video) {
            $originalVideos->add($video);
        }

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('trick', ['slug' => $trick->getSlug()]);
            }

            if (!$trick->getCreatedAt()) {
                $trick->setCreatedAt(new \DateTimeImmutable());
            }

            $trick->setUpdatedAt(new \DateTimeImmutable());

            foreach ($originalImages as $image) {
                if (false === $trick->getImages()->contains($image)) {
                    if ($image == $trick->getFeaturedImage()) {
                        $trick->setFeaturedImage(null);
                    }
                    $trick->removeImage($image);
                    if ($image == $trick->getFeaturedImage()) {
                        $newFeaturedImage = $trick->getImages()->getValues()[0];
                        $trick->setFeaturedImage($newFeaturedImage);
                    }
                }
            }
            foreach ($originalVideos as $video) {
                if (false === $trick->getVideos()->contains($video)) {
                    $trick->removeVideo($video);
                }
            }

            $manager->persist($trick);
            $manager->flush();

            if ($trick->getSlug() != $request->request->get('slug')) {
                return $this->redirectToRoute('trick', ['slug' => $trick->getSlug()]);
            }
        }

        return $this->render('trick/edit.html.twig', [
            'form' => $form->createView(),
            'trick' => $trick,
        ]);
    }

    /**
     * @Route("/trick/{slug}", name="trick")
     * @Route("/trick/{slug}/{tab}", name="trick")
     */
    public function trick(Request $request, Trick $trick, EntityManagerInterface $manager, SpamChecker $spamChecker, $tab = null): Response
    {
        $formCommentError = null;
        $tabs = [
            'gallery' => ['active' => false],
            'informations' => ['active' => false],
            'chat' => ['active' => false]
        ];
        $activeTab = 'gallery';
        if ($tab === 'informations' or $tab === 'chat') {
            $activeTab = $tab;
        }
        $tabs[$activeTab]['active'] = ' show active ';

        $comments = $trick->getComment();


        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $newComment = new Comment();
            $formComment = $this->createFormBuilder($newComment)
                ->add('comment', TextType::class, [
                    'label' => false
                ])
                ->add('submit', SubmitType::class, [
                    'label' => 'Envoyer'
                ])
                ->getForm();
            $formComment->handleRequest($request);

            if ($formComment->isSubmitted() && $formComment->isValid()) {
                $newComment->setTrick($trick)
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUser($this->getUser());
                $manager->persist($newComment);

                if ($spamChecker->getSpamScore($newComment) > 0) {
                    $formCommentError = 'Votre commentaire est considéré comme du spam. Veuillez écrire autre chose.';
                } else {
                    $manager->flush();
                }
            }
            $formCommentView = $formComment->createView();
        }

        return $this->render('trick/trick.html.twig', [
            'formComment' => $formCommentView ?? null,
            'formCommentError' => $formCommentError,
            'comments' => $comments->getValues(),
            'trick' => $trick,
            'tabs' => $tabs
        ]);
    }
}
