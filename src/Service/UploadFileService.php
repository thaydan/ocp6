<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadFileService
{
    private string $uploadDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $uploadDirectory, SluggerInterface $slugger)
    {
        $this->uploadDirectory = $uploadDirectory;
        $this->slugger = $slugger;
    }

    /**
     * @throws \Exception
     */
    public function upload(?UploadedFile $file): ?string
    {
        if($file){
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug(strtolower($originalFilename));
            $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            try {
                $file->move($this->getTargetDirectory(), $fileName);
            } catch (FileException $e) {
                throw new \Exception($e->getMessage());
            }
            return $fileName;
        }
        return null;
    }

    public function getTargetDirectory(): ?string
    {
        return $this->uploadDirectory;
    }
}