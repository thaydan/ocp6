<?php

namespace App\Listener\Doctrine;


use App\Entity\TrickImage;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class DeleteImageListener
{
    private string $uploadDirectory;

    public function __construct(string $uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof TrickImage) {
            return;
        }

        $file = $this->uploadDirectory . '/' . $entity->getFilename();
        if(file_exists($file)) {
            unlink($file);
        }
    }
}