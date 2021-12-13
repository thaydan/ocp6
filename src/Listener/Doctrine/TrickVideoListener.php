<?php

namespace App\Listener\Doctrine;


use App\Entity\TrickVideo;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TrickVideoListener
{
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof TrickVideo) {
            return;
        }

        preg_match_all("/^(?:https?:\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n?]+)/im", $entity->getUrl(),
            $matchesDomain, PREG_PATTERN_ORDER);
        $domain = $matchesDomain[1][0];

        $videoId = null;
        $matchesId = [];
        if ($domain == 'youtube.com') {
            preg_match_all("/^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/",
                $entity->getUrl(), $matchesId, PREG_PATTERN_ORDER);
            if (count($matchesId[2]) != 0) {
                $videoId = $matchesId[7][0];
            }
        } elseif ($domain == 'dailymotion.com') {
            preg_match_all("/^.+dailymotion.com\/(video|hub)\/([^_]+)[^#]*(#video=([^_&]+))?/",
                $entity->getUrl(), $matchesId, PREG_PATTERN_ORDER);
            if (count($matchesId[2]) != 0) {
                $videoId = $matchesId[2][0];
            }
        } elseif ($domain == 'vimeo.com') {
            preg_match_all("/(http|https)?:\/\/(www\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|)(\d+)(?:|\/\?)/",
                $entity->getUrl(), $matchesId, PREG_PATTERN_ORDER);
            if (count($matchesId[4]) != 0) {
                $videoId = $matchesId[4][0];
            }
        }

        if (!$videoId or !$domain) {
            return new \Exception("Aucune vidéo n'a été trouvée avec ce lien");
        }

        $entity->setPlatformDomain($domain);
        $entity->setPlatformVideoId($videoId);
    }
}