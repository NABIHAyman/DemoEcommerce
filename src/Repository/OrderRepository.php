<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Une petite méthode bonus pour calculer le revenu total proprement
     */
    public function countTotalRevenue(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.status = :status')
            ->setParameter('status', 'VALIDATED')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
