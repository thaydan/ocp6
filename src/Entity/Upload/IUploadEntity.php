<?php

namespace App\Entity\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface IUploadEntity
{
    public function getFilename(): ?string;

    public function setFilename(string $filename): self;

    public function getFile(): ?UploadedFile;

    public function setFile(?UploadedFile $file): self;
}