<?php

namespace AppBundle\Repository;

/**
 * CountryRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CountryRepository extends \Doctrine\ORM\EntityRepository
{
    public function findByName($country)
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('LOWER(c.name) = :country')
            ->setParameter('country', strtolower($country))
            ->getQuery()
            ->useResultCache(true)
            ->useQueryCache(true)
            ->getOneOrNullResult();
    }
}
