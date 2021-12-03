<?php

namespace App\Service;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker extends AbstractController
{
    private $client;
    private $endpoint;
    private ?Request $request;

    public function __construct(RequestStack $requestStack, HttpClientInterface $client)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->client = $client;
        $this->endpoint = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $_ENV['AKISMET_KEY']);
    }

    /**
     * @return int Spam score: 0: not spam, 1: maybe spam, 2: blatant spam
     *
     * @throws \RuntimeException if the call did not work
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getSpamScore(Comment $comment): int
    {
        $user = $this->getUser();


        $context = [
            'user_ip' => $this->request->getClientIp(),
            'user_agent' => $this->request->headers->get('user-agent'),
            'referrer' => $this->request->headers->get('referer'),
            'permalink' => $this->request->getUri(),
        ];

        $response = $this->client->request('POST', $this->endpoint, [
            'body' => array_merge($context, [
                'blog' => 'https://127.0.0.1:8000',
                'comment_type' => 'comment',
                'comment_author' => implode(" ", [
                    $user->getFirstName(),
                    $user->getLastName()
                ]),
                'comment_author_email' => $user->getEmail(),
                'comment_content' => $comment->getComment(),
                'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
                'blog_lang' => 'fr',
                'blog_charset' => 'UTF-8',
            ]),
        ]);

        $headers = $response->getHeaders();
        if ('discard' === ($headers['x-akismet-pro-tip'][0] ?? '')) {
            return 2;
        }

        $content = $response->getContent();
        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new \RuntimeException(sprintf('Unable to check for spam: %s (%s).', $content, $headers['x-akismet-debug-help'][0]));
        }

        return 'true' === $content ? 1 : 0;
    }
}