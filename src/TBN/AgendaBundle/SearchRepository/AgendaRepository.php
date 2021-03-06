<?php

namespace TBN\AgendaBundle\SearchRepository;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\MultiMatch;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Terms;
use FOS\ElasticaBundle\Paginator\PaginatorAdapterInterface;
use FOS\ElasticaBundle\Repository;
use TBN\AgendaBundle\Search\SearchAgenda;
use TBN\MainBundle\Entity\Site;

class AgendaRepository extends Repository
{
    /**
     * @param $site Site
     * @param $search SearchAgenda
     *
     * @return PaginatorAdapterInterface
     */
    public function findWithSearch(Site $site, SearchAgenda $search)
    {
        $mainQuery = new BoolQuery();

        //Filters
        $mainQuery->addMust(
            new Term(['site.id' => $site->getId()])
        );

        if ($search->getDu()) {
            if (!$search->getAu()) {
                $mainQuery->addMust(new Range('date_fin', [
                    'gte' => $search->getDu()->format('Y-m-d'),
                ]));
            } else {
                $filterDate = new BoolQuery();
                /*
                 * 4 cas :
                 * 1) [debForm; finForm] € [deb; fin]
                 * 2) [deb; fin] € [debForm; finForm]
                 * 3) deb € [debForm; finForm]
                 * 4) fin € [debForm; finForm]
                */

                //Cas1 : [debForm; finForm] € [deb; fin] -> (deb < debForm AND fin > finForm)
                $cas1 = new BoolQuery();
                $cas1->addMust(new Range('date_debut', [
                        'lte' => $search->getDu()->format('Y-m-d'),
                    ])
                )->addMust(new Range('date_fin', [
                    'gte' => $search->getAu()->format('Y-m-d'),
                ]));

                //Cas2 : [deb; fin] € [debForm; finForm] -> (deb > debForm AND fin < finForm)
                $cas2 = new BoolQuery();
                $cas2->addMust(new Range('date_debut', [
                        'gte' => $search->getDu()->format('Y-m-d'),
                    ])
                )->addMust(new Range('date_fin', [
                    'lte' => $search->getAu()->format('Y-m-d'),
                ]));

                //Cas3 : deb € [debForm; finForm] -> (deb > debForm AND deb < finForm)
                $cas3 = new Range('date_debut', [
                    'gte' => $search->getDu()->format('Y-m-d'),
                    'lte' => $search->getAu()->format('Y-m-d'),
                ]);

                //Cas4 : fin € [debForm; finForm] -> (fin > debForm AND fin < finForm)
                $cas4 = new Range('date_fin', [
                    'gte' => $search->getDu()->format('Y-m-d'),
                    'lte' => $search->getAu()->format('Y-m-d'),
                ]);

                $filterDate
                    ->addShould($cas1)
                    ->addShould($cas2)
                    ->addShould($cas3)
                    ->addShould($cas4);

                $mainQuery->addMust($filterDate);
            }
        }

        //Query
        if ($search->getTerm()) {
            $query = new MultiMatch();
            $query->setQuery($search->getTerm())
                ->setFields([
                    'nom', 'descriptif', 'type_manifestation',
                    'theme_manifestation', 'categorie_manifestation', 'place.nom',
                    'place.rue', 'place.ville', 'place.code_postal',
                ])
                ->setOperator(false !== \strstr($search->getTerm(), ',') ? 'or' : 'and')
                ->setFuzziness(0.8)
                ->setMinimumShouldMatch('80%');
            $mainQuery->addMust($query);
        }

        if ($search->getLieux()) {
            $placeQuery = new Terms('place.id', $search->getLieux());
            $mainQuery->addMust($placeQuery);
        }

        if ($search->getCommune()) {
            $query = (new Match())->setField('place.ville', \implode(',', $search->getCommune()));
            $mainQuery->addMust($query);
        }

        if ($search->getTag()) {
            $query = new MultiMatch();
            $query->setQuery($search->getTag())
                ->setFields(['type_manifestation', 'theme_manifestation', 'categorie_manifestation']);
            $mainQuery->addMust($query);
        }

        if ($search->getTypeManifestation()) {
            $communeTypeManifestationQuery = new Match();
            $communeTypeManifestationQuery->setField('type_manifestation', \implode(' ', $search->getTypeManifestation()));
            $mainQuery->addMust($communeTypeManifestationQuery);
        }

        //Construction de la requête finale
        $finalQuery = Query::create($mainQuery)
            ->addSort(['date_fin' => 'asc'])
            ->addSort(['fb_participations' => ['order' => 'desc', 'unmapped_type' => 'integer']]);

        return $this->createPaginatorAdapter($finalQuery);
    }
}
