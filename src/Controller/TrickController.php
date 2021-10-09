<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Repository\TrickRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrickController extends AbstractController
{
    /**
     * @Route("/tricks", name="tricks")
     */
    public function tricks(TrickRepository $trickRepository): Response
    {
        $tricks = $trickRepository->findAll();

        return $this->render('trick/tricks.html.twig', [
            'tricks' => $tricks
        ]);
    }

    /**
     * @Route("/trick/new", name="trick_new")
     * @Route("/trick/{slug}/edit", name="trick_edit")
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

        $form = $this->createFormBuilder($trick)
            ->add('title')
            ->add('slug')
            ->add('description')
            ->add('content')
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trick->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $manager->persist($trick);
            $manager->flush();

            if ($slug != $request->request->get('slug')) {
                return $this->redirectToRoute('trick', ['slug' => $trick->getSlug()]);
            }
        }

        return $this->render('trick/edit.html.twig', [
            'formTrick' => $form->createView(),
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
            ->add('comment')
            ->add('submit', SubmitType::class)
            ->getForm();

        $formComment->handleRequest($request);

        if ($formComment->isSubmitted() && $formComment->isValid()) {
            $newComment->setTrick($trick)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUser($this->getUser());

            $manager->persist($newComment);

            /*$context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            if (2 === $spamChecker->getSpamScore($newComment, $context)) {
                throw new \RuntimeException('Blatant spam, go away!');
            }*/

            $manager->flush();
        }

        return $this->render('trick/trick.html.twig', [
            'formComment' => $formComment->createView(),
            'comments' => $comments->getValues(),
            'trick' => $trick
        ]);
    }
}
