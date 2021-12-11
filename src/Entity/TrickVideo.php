<?php

namespace App\Entity;

use App\Repository\TrickVideoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TrickVideoRepository::class)
 * @UniqueEntity(fields={"platformDomain", "platformVideoId"})
 */
class TrickVideo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      min = 2,
     *      minMessage = "Le titre doit fait plus de {{ limit }} caractères."
     * )
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $platformDomain;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $platformVideoId;

    /**
     * @ORM\ManyToOne(targetEntity=Trick::class, inversedBy="videos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trick;

    private string $url;

    public function __toString()
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPlatformDomain(): ?string
    {
        return $this->platformDomain;
    }

    public function setPlatformDomain(string $platformDomain): self
    {
        $this->platformDomain = $platformDomain;

        return $this;
    }

    public function getPlatformVideoId(): ?string
    {
        return $this->platformVideoId;
    }

    public function setPlatformVideoId(string $platformVideoId): self
    {
        $this->platformVideoId = $platformVideoId;

        return $this;
    }

    public function getTrick(): ?Trick
    {
        return $this->trick;
    }

    public function setTrick(?Trick $trick): self
    {
        $this->trick = $trick;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url ?? null;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
