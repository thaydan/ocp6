<?php

namespace App\Controller;

use App\Entity\Trick;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @Route("/tricks/{pageNumber}", name="home_pagination")
     */
    public function index($pageNumber = 1): Response
    {
        $repo = $this->getDoctrine()->getRepository(Trick::class);

        /* tricks pagination */
        $paginationTricks = [];
        $paginationTricks['pageNumber'] = $pageNumber;
        $paginationTricks['itemsCount'] = $repo->count([]);
        if($paginationTricks['pageNumber'] > 1) {
            if($paginationTricks['pageNumber'] == 2) {
                $paginationTricks['linkPrevious'] = $this->generateUrl('home') . '#tricks';
            }
            else {
                $paginationTricks['linkPrevious'] = $this->generateUrl('home_pagination', [
                        'pageNumber' => $paginationTricks['pageNumber'] - 1
                    ]) . '#tricks';
            }
        }
        if($paginationTricks['itemsCount'] > $paginationTricks['pageNumber'] * 10) {
            $paginationTricks['linkNext'] = $this->generateUrl('home_pagination', [
                'pageNumber' => $paginationTricks['pageNumber'] + 1
            ]) . '#tricks';
        }
        /* end tricks pagination */

        $tricks = $repo->findBy([], null, 10, ($paginationTricks['pageNumber'] - 1) * 10);

        return $this->render('home/index.html.twig', [
            'isHeaderTransparent' => true,
            'tricks' => $tricks,
            'paginationTricks' => $paginationTricks
        ]);
    }
}
