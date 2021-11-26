<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LostPasswordType;
use App\Form\SignUpType;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Referer $referer): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $referer->goTo();
        }
        $referer->set();

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    /**
     * @Route("/sign-up", name="app_sign_up")
     */
    public function signUp(Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('home');
        }

        $error = null;
        $signUpConfirmation = false;

        $form = $this->createForm(SignUpType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formEmail = $form->get('email')->getData();
            $userWithSameEmail = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['email' => $formEmail]);
            if (!$userWithSameEmail) {
                $user = new User();
                $user->setUsername($form->get('username')->getData())
                    ->setFirstName($form->get('firstName')->getData())
                    ->setLastName($form->get('lastName')->getData())
                    ->setPassword($this->userPasswordHasher->hashPassword($user, $form->get('password')->getData()))
                    ->setEmail($formEmail);
                $manager->persist($user);
                $manager->flush();
                $signUpConfirmation = true;
            } else {
                $error = 'Cette adresse e-mail est déjà utilisée';
            }
        }

        return $this->render('security/sign_up.html.twig', [
            'form' => $form->createView(),
            'signUpConfirmation' => $signUpConfirmation,
            'error' => $error
        ]);
    }


    /**
     * @Route("/lost-password", name="app_lost_password")
     */
    public function lostPassword(Request $request): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('home');
        }

        $lastUsername = '';
        $error = '';


        $form = $this->createForm(LostPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        }

        return $this->render('security/lost_password.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }


    /**
     * @Route("/reset-password/{token}", name="app_reset_password")
     */
    public function resetPassword(Request $request): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('home');
        }

        $lastUsername = '';
        $error = '';


        $form = $this->createForm(LostPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        }

        return $this->render('security/reset_password.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }
}
