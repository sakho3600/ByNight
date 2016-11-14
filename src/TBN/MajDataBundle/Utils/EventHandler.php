<?php

namespace TBN\MajDataBundle\Utils;

use TBN\AgendaBundle\Entity\Agenda;
use TBN\AgendaBundle\Entity\Place;
use TBN\MainBundle\Entity\Site;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * Description of EventHandler
 *
 * @author Guillaume Sainthillier <guillaume.sainthillier@gmail.com>
 */
class EventHandler
{
    private $firewall;
    private $cleaner;
    private $comparator;
    private $merger;

    public function __construct(Firewall $firewall, Cleaner $cleaner, Comparator $comparator, Merger $merger)
    {
        $this->firewall = $firewall;
        $this->cleaner = $cleaner;
        $this->comparator = $comparator;
        $this->merger = $merger;
    }

    public function updateImage(Agenda $agenda, $newURL) {
        if(true || $agenda->getPath() === null || ($newURL !== null && $agenda->getUrl() !== $newURL)) {
            $agenda->setUrl($newURL);
            $this->downloadImage($agenda);
        }
    }

    public function downloadImage(Agenda &$agenda)
    {
        //$url = preg_replace('/([^:])(\/{2,})/', '$1/', $agenda->getUrl());
        $url = $agenda->getUrl();
        $path = $agenda->getPath();
        $agenda->setUrl(null)->setPath(null);

        if(! $url) {
            $agenda->setPath($path);
            return;
        }

        try {
            $image = file_get_contents($url);
        } catch (\Exception $ex) {
            $image = null;
        }

        if ($image) {
            $agenda->setUrl($url);

            //En cas d'url du type:  http://u.rl/image.png?params
            $ext = preg_replace("/(\?|_)(.*)$/", "", pathinfo($url, PATHINFO_EXTENSION));

            $filename = sha1(uniqid(mt_rand(), true)) . "." . $ext;

            $tempPath = sys_get_temp_dir().'/'.$filename;
            $octets = file_put_contents($tempPath, $image);

            if ($octets > 0) {
                $file = new UploadedFile($tempPath, $filename, null, null, false, true);
                $agenda->setFile($file);
            }
        }
    }

    /**
     * @param array $persistedPlaces
     * @param Site $site
     * @param Agenda $agenda
     * @return Agenda|null
     */
    public function handle(array &$persistedPlaces, Site &$site, Agenda &$agenda)
    {
        //Assignation du site
        $agenda->setSite($site);

        $tmpPlace = $agenda->getPlace();
        if ($tmpPlace !== null) //Analyse de la place
        {
            //Anticipation par traitement du blacklistage de la place;
            if ($tmpPlace->getFacebookId()) {
                $exploration = $this->firewall->getExploration($tmpPlace->getFacebookId(), $site);
                if (null !== $exploration && $exploration->getBlackListed() === true) {
                    return null;
                }
            }

            //Recherche d'une meilleure place déjà existante
            $tmpPlace->setSite($site);
            $tmpPlace = Monitor::bench('Clean Place', function () use (&$tmpPlace) {
                return $this->cleaner->getCleanedPlace($tmpPlace);
            });

            $place = Monitor::bench('Handle Place', function () use (&$tmpPlace, &$persistedPlaces, &$agenda) {
                return $this->handlePlace($persistedPlaces, $tmpPlace);
            });

            $agenda->setPlace($place);
        }

        //Nettoyage de l'événement
        return Monitor::bench('Clean Event', function () use (&$agenda) {
            return $this->cleaner->cleanEvent($agenda);
        });
    }

    public function handleEvent(array $persistedEvents, Agenda $testedAgenda = null)
    {
        if (null !== $testedAgenda && $this->firewall->isGoodEvent($testedAgenda)) {
            //Evenement persisté
            $bestEvent = Monitor::bench('getBestEvent', function () use (&$persistedEvents, &$testedAgenda) {
                return $this->comparator->getBestEvent($persistedEvents, $testedAgenda);
            });

            //On fusionne l'event existant avec celui découvert (même si NULL)
            return Monitor::bench('mergeEvent', function () use (&$bestEvent, &$testedAgenda) {
                return $this->merger->mergeEvent($bestEvent, $testedAgenda);
            });
        }
        return null;
    }

    public function handlePlace(array &$persistedPlaces, Place &$testedPlace = null)
    {
        $isGoodPlace = Monitor::bench('isGoodPlace', function () use (&$testedPlace) {
            return $this->firewall->isGoodPlace($testedPlace);
        });

        if ($isGoodPlace) {
            $bestPlace = Monitor::bench('getBestPlace', function () use (&$persistedPlaces, &$testedPlace) {
                return $this->comparator->getBestPlace($persistedPlaces, $testedPlace);
            });

            return Monitor::bench('mergePlace', function () use (&$bestPlace, &$testedPlace) {
                return $this->merger->mergePlace($bestPlace, $testedPlace);
            });
        }

        return null;
    }
}
