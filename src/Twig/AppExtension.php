<?php

namespace App\Twig;

use App\Entity\SidebarItem;
use App\Entity\TrickImage;
use App\Repository\SidebarItemRepository;
use Doctrine\Persistence\ManagerRegistry;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private Environment $environment;
    private ManagerRegistry $doctrine;

    public function __construct(Environment $environment, ManagerRegistry $registry)
    {
        $this->environment = $environment;
        $this->doctrine = $registry;
    }

    /* initialisation of getFilters and getFunctions */
    public function getFilters()
    {
        return [
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('image', [$this, 'generateImageLink']),
        ];
    }
    /* end initialisation */




    function generateImageLink(TrickImage $image): string
    {
        return $_ENV['UPLOAD_DIRECTORY'] . '/' . $image->getFilename();
    }
}