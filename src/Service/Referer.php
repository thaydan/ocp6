<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Referer
{
    private Request $request;
    private $requestSession;
    private $router;


    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $router)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->requestSession = $this->request->getSession();
        $this->router = $router;
    }

    public function set() {
        $currentRouteName = $this->request->get('_route');
        var_dump($currentRouteName);
        if($currentRouteName != 'app_login') {
            return $this->requestSession->set('referer', $this->request->headers->get('referer'));
        }
    }

    public function get() {
        return $this->requestSession->get('referer');
    }

    public function goTo() {
        if ($this->get()) {
            return new RedirectResponse($this->get());
        }
        else {
            return new RedirectResponse($this->router->generate('home'));
        }
    }

    public function setAndGo () {
        $this->set();
        return $this->goTo();
    }
}