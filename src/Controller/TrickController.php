<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\TrickType;
use App\Repository\CommentRepository;
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
     * @IsGranted("ROLE_USER")
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


        if ($form->isSubmitted() && $form->isValid() && !empty($trick->getImages()->getValues())) {
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
                }
            }
            foreach ($originalVideos as $video) {
                if (false === $trick->getVideos()->contains($video)) {
                    $trick->removeVideo($video);
                }
            }

            if (!$trick->getFeaturedImage()) {
                $newFeaturedImage = $trick->getImages()->getValues()[0];
                $trick->setFeaturedImage($newFeaturedImage);
            }

            $manager->persist($trick);
            $manager->flush();

            $this->addFlash(
                'success',
                'Les modifications ont été sauvegardées'
            );

            if ($trick->getSlug() != $request->request->get('slug')) {
                return $this->redirectToRoute('trick', ['slug' => $trick->getSlug()]);
            }
        }

        return $this->render('trick/edit.html.twig', [
            'form' => $form->createView(),
            'trick' => $trick
        ]);
    }


    /**
     * @Route("/trick/{slug}", name="trick")
     * @Route("/trick/{slug}/{tab}", name="trick")
     * @Route("/trick/{slug}/{tab}/{pageNumber}", name="trick")
     */
    public function trick(
        Request     $request, Trick $trick, EntityManagerInterface $manager,
        SpamChecker $spamChecker, $tab = null, $pageNumber = 1, CommentRepository $commentRepository): Response
    {
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


        /* form add comment */
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
                    $this->addFlash(
                        'danger',
                        'Votre commentaire a été refusé.<br>Veuillez écrire autre chose.'
                    );
                } else {
                    $manager->flush();

                    $this->addFlash(
                        'success',
                        'Votre commentaire a été ajouté.'
                    );
                }
            }
            $formCommentView = $formComment->createView();
        }
        /* end form add comment */

        /* comments pagination */
        $paginationComments = [];
        $paginationComments['pageNumber'] = $pageNumber;
        $paginationComments['itemsCount'] = $commentRepository->count(['trick' => $trick]);
        if ($paginationComments['pageNumber'] > 1) {
            $paginationComments['linkPrevious'] = $this->generateUrl('trick', [
                'slug' => $trick->getSlug(),
                'tab' => 'chat',
                'pageNumber' => $paginationComments['pageNumber'] - 1
            ]);
        }
        if ($paginationComments['itemsCount'] > $paginationComments['pageNumber'] * 10) {
            $paginationComments['linkNext'] = $this->generateUrl('trick', [
                'slug' => $trick->getSlug(),
                'tab' => 'chat',
                'pageNumber' => $paginationComments['pageNumber'] + 1
            ]);
        }
        /* end comment pagination */
        $comments = $commentRepository->findBy(['trick' => $trick], ['createdAt' => 'DESC'], 10, ($paginationComments['pageNumber'] - 1) * 10);

        return $this->render('trick/trick.html.twig', [
            'formComment' => $formCommentView ?? null,
            'comments' => $comments,
            'paginationComments' => $paginationComments,
            'trick' => $trick,
            'tabs' => $tabs
        ]);


    }


    /**
     * @Route("/delete-trick/{slug}", name="trick_delete", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function delete(Request $request, Trick $trick): Response
    {
        if ($this->isCsrfTokenValid('delete' . $trick->getSlug(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($trick);
            $entityManager->flush();
        }

        $this->addFlash('success', "Le trick a été supprimé avec succès");

        return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
    }
}
