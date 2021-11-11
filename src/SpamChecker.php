<?php

namespace App;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker extends AbstractController
{
    private $client;
    private $endpoint;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->endpoint = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $_ENV['AKISMET_KEY']);
    }

    /**
     * @return int Spam score: 0: not spam, 1: maybe spam, 2: blatant spam
     *
     * @throws \RuntimeException if the call did not work
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getSpamScore(Comment $comment, array $context): int
    {
        $user = $this->getUser();

        $response = $this->client->request('POST', $this->endpoint, [
            'body' => array_merge($context, [
                'blog' => 'https://127.0.0.1:8000',
                'comment_type' => 'comment',
                'comment_author' => implode(" ", [
                    $user->getFirstName(),
                    $user->getLastName()
                ]),
                'comment_author_email' => $user->getEmail(),
                /*'comment_author_email' => 'akismet-guaranteed-spam@example.com',*/
                'comment_content' => $comment->getComment(),
                'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
                'blog_lang' => 'fr',
                'blog_charset' => 'UTF-8',
                'is_test' => true,
            ]),
        ]);

        $headers = $response->getHeaders();
        dd($response->getContent());
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