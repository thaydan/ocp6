<?php

namespace App\Listener\Doctrine;


use App\Entity\Upload\AUploadEntity;
use App\Service\UploadFileService;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class UploaderListener
{
    private UploadFileService $uploadFileService;

    public function __construct(UploadFileService $uploadFileService)
    {
        $this->uploadFileService = $uploadFileService;
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof AUploadEntity || !$entity->getFile()) {
            return;
        }

        $entity->setFilename(
            $this->uploadFileService->upload(
                $entity->getFile()
            )
        );
    }
}