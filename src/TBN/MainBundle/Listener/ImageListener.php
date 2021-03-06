<?php

namespace TBN\MainBundle\Listener;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use TBN\AgendaBundle\Entity\Agenda;
use TBN\MainBundle\Entity\Site;
use TBN\UserBundle\Entity\User;
use Vich\UploaderBundle\Event\Event;

class ImageListener
{
    private $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function onImageDelete(Event $event)
    {
        $object = $event->getObject();

        if ($object instanceof User) {
            $filters = ['thumb_user_large', 'thumb_user_evenement', 'thumb_user', 'thumb_user_menu', 'thumb_user_50', 'thumb_user_115'];
        } elseif ($object instanceof Agenda) {
            $filters = ['thumbs_evenement', 'thumb_evenement'];
        } elseif ($object instanceof Site) {
            $filters = ['thumb_site', 'thumb_site_large'];
        } else {
            $filters = [];
        }

        $prefix = $event->getMapping()->getUriPrefix();
        $path   = $prefix.'/'.$event->getMapping()->getFileName($object);

        foreach ($filters as $filter) {
            if ($this->cacheManager->isStored($path, $filter)) {
                $this->cacheManager->remove($path, $filter);
            }
        }
    }
}
