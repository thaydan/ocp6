<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Referer
{
    private Request $request;
    private $requestSession;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->requestSession = $this->request->getSession();
    }

    public function set() {
        return $this->requestSession->set('referer', $this->request->headers->get('referer'));
    }

    public function get() {
        return $this->requestSession->get('referer');
    }

    public function goTo() {
        if ($this->get()) {
            return new RedirectResponse($this->get());
        }
    }

    public function setAndGo () {
        $this->set();
        return $this->goTo();
    }
}