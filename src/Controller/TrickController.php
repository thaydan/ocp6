<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\TrickType;
use App\SpamChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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
     */
    public function edit(Request $request, EntityManagerInterface $manager, Trick $trick = null, $slug = null): Response
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

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
            if($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('trick', ['slug' => $trick->getSlug()]);
            }

            $trick->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());


            foreach ($originalImages as $image) {
                if (false === $trick->getImages()->contains($image)) {
                    $trick->removeImage($image);
                }
            }
            foreach ($originalVideos as $video) {
                if (false === $trick->getVideos()->contains($video)) {
                    $trick->removeVideo($video);
                }
            }

            $manager->persist($trick);
            $manager->flush();

            if ($slug != $request->request->get('slug')) {
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
     */
    public function trick(Request $request, Trick $trick, EntityManagerInterface $manager, SpamChecker $spamChecker): Response
    {
        $comments = $trick->getComment();

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

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            if (2 === $spamChecker->getSpamScore($newComment, $context)) {
                throw new \RuntimeException('Blatant spam, go away!');
            }

            $manager->flush();
        }

        return $this->render('trick/trick.html.twig', [
            'formComment' => $formComment->createView(),
            'comments' => $comments->getValues(),
            'trick' => $trick
        ]);
    }
}
