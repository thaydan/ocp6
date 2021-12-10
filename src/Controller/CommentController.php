<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Service\Referer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class CommentController extends AbstractController
{
    /**
     * @isGranted("ROLE_USER")
     * @Route("/comment/{id}", name="comment_delete", methods={"POST"})
     */
    public function delete(Request $request, Comment $comment, Referer $referer): Response
    {
        if($comment->getUser() != $this->getUser()) {
            return $this->redirectToRoute('home');
        }
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }
        return $referer->setAndGo();
    }
}
