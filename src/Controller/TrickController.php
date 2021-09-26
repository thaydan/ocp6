<?php

namespace App\Controller;

use App\Entity\Trick;
use App\Repository\TrickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        if ($request->request->count() > 0) {
            $trick->setTitle($request->request->get('title'))
                ->setSlug($request->request->get('slug'))
                ->setDescription($request->request->get('description'))
                ->setContent($request->request->get('content'))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $manager->persist($trick);
            $manager->flush();

            if ($slug != $request->request->get('slug')) {
                return $this->redirectToRoute('trick', ['slug' => $request->request->get('slug')]);
            }
        }

        return $this->render('trick/edit.html.twig', [
            'trick' => $trick,
        ]);
    }

    /**
     * @Route("/trick/{slug}", name="trick")
     */
    public function trick(Trick $trick): Response
    {
        return $this->render('trick/trick.html.twig', [
            'trick' => $trick,
        ]);
    }
}
