<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class ShippingMethodConfigRepository extends EntityRepository
{
    /**
     * @param string $method
     */
    public function deleteByMethod($method)
    {
        $qb = $this->createQueryBuilder('methodConfig');

        $qb->delete()
            ->where(
                $qb->expr()->eq('methodConfig.method', ':method')
            )
            ->setParameter('method', $method);

        $qb->getQuery()->execute();
    }

    /**
     * @return array
     */
    public function findIdsWithoutTypeConfigs()
    {
        $qb = $this->createQueryBuilder('methodConfig');
        $qb->select('methodConfig.id')
            ->leftJoin('methodConfig.typeConfigs', 'typeConfig')
            ->where($qb->expr()->isNull('typeConfig.id'));

        return array_column($qb->getQuery()->execute(), 'id');
    }

    /**
     * @param array $ids
     */
    public function deleteByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('methodConfig');
        $qb->delete()
            ->where($qb->expr()->in('methodConfig.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery()->execute();
    }
}
