<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Form\LostPasswordType;
use App\Form\ResetPasswordType;
use App\Form\SignUpType;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    /**
     * @Route("/sign-up", name="app_sign_up")
     */
    public function signUp(Request $request, EntityManagerInterface $manager, MailerInterface $mailer): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('home');
        }

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
                $token = bin2hex(random_bytes(20));
                $user = new User();
                $user->setUsername($formUsername)
                    ->setFirstName($form->get('firstName')->getData())
                    ->setLastName($form->get('lastName')->getData())
                    ->setPassword($this->userPasswordHasher->hashPassword($user, $form->get('password')->getData()))
                    ->setEmail($formEmail)
                    ->setToken($token);
                $manager->persist($user);
                $manager->flush();

                $payload = [
                    "email" => $user->getEmail(),
                    "token" => $token
                ];
                $tokenEncoded = JWT::encode($payload, $_ENV['APP_SECRET'], 'HS256');

                $confirmationLink = $request->getUriForPath($this->generateUrl('app_confirm_account', ['token' => $tokenEncoded]));
                $email = (new Email())
                    ->from(new Address($_ENV['MAILER_SENDER_EMAIL'], $_ENV['MAILER_SENDER_NAME']))
                    ->to($user->getEmail())
                    ->subject('Activation de votre compte')
                    ->text('Activer mon compte : ' . $confirmationLink)
                    ->html('<p><a href="' . $confirmationLink . '">Activer mon compte</a></p>');
                $mailer->send($email);

                $signUpConfirmation = true;
            } elseif ($userWithSameUsername) {
                $this->addFlash(
                    'danger',
                    'Ce pseudo est déjà utilisé'
                );
            } elseif ($userWithSameEmail) {
                $this->addFlash(
                    'danger',
                    'Cette adresse e-mail est déjà utilisée'
                );
            }
        }

        return $this->render('security/sign_up.html.twig', [
            'form' => $form->createView(),
            'signUpConfirmation' => $signUpConfirmation
        ]);
    }


    /**
     * @Route("/lost-password", name="app_lost_password")
     * @throws TransportExceptionInterface
     */
    public function lostPassword(Request $request, MailerInterface $mailer, EntityManagerInterface $manager): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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

                $payload = [
                    "email" => $userWithThisEmail->getEmail(),
                    "token" => $token
                ];
                $tokenEncoded = JWT::encode($payload, $_ENV['APP_SECRET'], 'HS256');

                $resetPasswordLink = $request->getUriForPath($this->generateUrl('app_reset_password_token', ['token' => $tokenEncoded]));
                $email = (new Email())
                    ->from(new Address($_ENV['MAILER_SENDER_EMAIL'], $_ENV['MAILER_SENDER_NAME']))
                    ->to($userWithThisEmail->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->text('Réinitialiser mon mot de passe : ' . $resetPasswordLink)
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
     * @Route("/reset-password", name="app_reset_password")
     * @Route("/reset-password/{token}", name="app_reset_password_token")
     */
    public function resetPassword(?string $token, Request $request, EntityManagerInterface $manager): Response
    {
        $user = null;
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            $user = $this->getUser();
        }
        if ($token) {
            try {
                $jwt = JWT::decode($token, new Key($_ENV['APP_SECRET'], 'HS256'));
            } catch (Exception $e) {
                return $this->redirectToRoute('home');
            }

            $user = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['email' => $jwt->email, 'token' => $jwt->token]);
        }
        if (!$user) {
            return $this->redirectToRoute('home');
        }

        $error = '';
        $resetPasswordConfirmation = false;

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $form->get('password')->getData()))
                ->setToken(null);
            $manager->persist($user);
            $manager->flush();
            $resetPasswordConfirmation = true;
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
            'resetPasswordConfirmation' => $resetPasswordConfirmation,
            'error' => $error
        ]);
    }


    /**
     * @Route("/confirm-account/{token}", name="app_confirm_account")
     */
    public function confirmAccount(?string $token, Request $request, EntityManagerInterface $manager): Response
    {
        if ($token) {
            try {
                $jwt = JWT::decode($token, new Key($_ENV['APP_SECRET'], 'HS256'));
            } catch (Exception $e) {
                return $this->redirectToRoute('home');
            }

            $user = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['email' => $jwt->email, 'token' => $jwt->token]);

            if (!$user) {
                return $this->redirectToRoute('home');
            }

            $user->setToken(null)
                ->setAccountConfirmed(true);
            $manager->persist($user);
            $manager->flush();
        }
        $this->addFlash('success', 'Votre compte a été validé avec succès.');
        return $this->redirectToRoute('app_login');
    }


    /**
     * @Route("/account", name="app_account")
     * @IsGranted("ROLE_USER")
     */
    public function account(Request $request, EntityManagerInterface $manager): Response
    {
        $error = '';
        $success = false;

        $user = $this->getUser();
        $form = $this->createForm(AccountType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userWithSameUsername = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['username' => $form->get('username')->getData()]);
            if ($user == $userWithSameUsername or !$userWithSameUsername) {
                $manager->persist($user);
                $manager->flush();
                $this->addFlash(
                    'success',
                    'Les modifications ont bien été sauvegardées'
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Ce pseudo est déjà utilisé'
                );
            }
        }

        return $this->render('security/account.html.twig', [
            'form' => $form->createView(),
            'success' => $success,
            'error' => $error
        ]);
    }
}
