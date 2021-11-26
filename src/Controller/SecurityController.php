<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LostPasswordType;
use App\Form\ResetPasswordType;
use App\Form\SignUpType;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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
            //return $referer->goTo();
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
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
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
            $formUsername = $form->get('username')->getData();
            $userWithSameUsername = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['username' => $formUsername]);
            $formEmail = $form->get('email')->getData();
            $userWithSameEmail = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['email' => $formEmail]);
            if (!$userWithSameEmail && !$userWithSameUsername) {
                $user = new User();
                $user->setUsername($formUsername)
                    ->setFirstName($form->get('firstName')->getData())
                    ->setLastName($form->get('lastName')->getData())
                    ->setPassword($this->userPasswordHasher->hashPassword($user, $form->get('password')->getData()))
                    ->setEmail($formEmail);
                $manager->persist($user);
                $manager->flush();
                $signUpConfirmation = true;
            } else {
                if ($userWithSameUsername) {
                    $error = 'Ce pseudo est déjà utilisé';
                } else if ($userWithSameEmail) {
                    $error = 'Cette adresse e-mail est déjà utilisée';
                }
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
     * @throws TransportExceptionInterface
     */
    public function lostPassword(Request $request, MailerInterface $mailer, EntityManagerInterface $manager): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('home');
        }

        $error = '';
        $lostPasswordConfirmation = false;

        $form = $this->createForm(LostPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formEmail = $form->get('email')->getData();
            $userWithThisEmail = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['email' => $formEmail]);
            if ($userWithThisEmail) {
                $token = bin2hex(random_bytes(20));
                $userWithThisEmail->setToken($token);
                $manager->persist($userWithThisEmail);
                $manager->flush();

                $key = $_ENV['APP_SECRET'];
                $payload = [
                    "email" => $userWithThisEmail->getEmail(),
                    "token" => $token
                ];
                $tokenEncoded = JWT::encode($payload, $key, 'HS256');

                $resetPasswordLink = $request->getUriForPath($this->generateUrl('app_reset_password', ['token' => $tokenEncoded]));
                $email = (new Email())
                    ->from(new Address($_ENV['MAILER_SENDER_EMAIL'], $_ENV['MAILER_SENDER_NAME']))
                    ->to('rominoudu85@gmail.com')
                    ->subject('Réinitialisation du mot de passe')
                    ->text('Sending emails is fun again!')
                    ->html('<p><a href="' . $resetPasswordLink . '">Réinitialiser mon mot de passe</a></p>');
                $mailer->send($email);
                $lostPasswordConfirmation = true;
            }

            $error = "Aucun compte n'existe avec cette adresse e-mail.";
        }

        return $this->render('security/lost_password.html.twig', [
            'form' => $form->createView(),
            'lostPasswordConfirmation' => $lostPasswordConfirmation,
            'error' => $error
        ]);
    }


    /**
     * @Route("/reset-password/{token}", name="app_reset_password")
     */
    public function resetPassword(string $token, Request $request, EntityManagerInterface $manager): Response
    {
        try {
            $jwt = JWT::decode($token, new Key($_ENV['APP_SECRET'], 'HS256'));
        } catch (\Exception $e) {
            return $this->redirectToRoute('home');
        }

        $user = $this->getDoctrine()->getRepository(User::class)
            ->findOneBy(['email' => $jwt->email, 'token' => $jwt->token]);
        if(!$user) {
            return $this->redirectToRoute('home');
        }

        $error = '';
        $resetPasswordConfirmation = false;

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['email' => $jwt->email, 'token' => $jwt->token]);
            if ($user) {
                $user->setPassword($this->userPasswordHasher->hashPassword($user, $form->get('password')->getData()))
                    ->setToken(null);
                $manager->persist($user);
                $manager->flush();
                $resetPasswordConfirmation = true;
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
            'resetPasswordConfirmation' => $resetPasswordConfirmation,
            'error' => $error
        ]);
    }
}
