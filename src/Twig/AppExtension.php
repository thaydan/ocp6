<?php

namespace App\Twig;

use App\Entity\TrickImage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private string $uploadDirectory;

    public function __construct(string $uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
    }

    /* initialisation of getFilters and getFunctions */
    public function getFilters()
    {
        return [];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('image', [$this, 'generateImageLink']),
            new TwigFunction('gravatar', [$this, 'gravatar'], ['is_safe' => ['html']]),
        ];
    }
    /* end initialisation */




    function generateImageLink(?TrickImage $image): string
    {
        if(!$image) {
            return '';
        }
        return $this->uploadDirectory . '/' . $image->getFilename();
    }

    function gravatar($email): string
    {
        if(!$email) {
            return '';
        }
        $hash = md5(strtolower(trim($email)));
        return '<img src="https://www.gravatar.com/avatar/'. $hash .'?d=mp">';
    }
}
