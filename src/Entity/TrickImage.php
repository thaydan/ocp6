<?php

namespace App\Entity;

use App\Entity\Upload\AUploadEntity;
use App\Repository\TrickImageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TrickImageRepository::class)
 */
class TrickImage extends AUploadEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $filename;

    /**
     * @ORM\ManyToOne(targetEntity=Trick::class, inversedBy="images")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trick;

    public function __toString()
    {
        return $this->filename;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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

    public function getUploadDirectory(string $uploadDirectory): self
    {
        return $_ENV['UPLOAD_DIRECTORY'];
    }
}
