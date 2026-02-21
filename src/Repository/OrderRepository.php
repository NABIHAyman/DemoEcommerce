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

    /**
     * Récupère un bloc spécifique de commandes pour un utilisateur (LIMIT & OFFSET)
     */
    public function findPaginatedByUser($user, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC') // Les plus récentes en premier
            ->setFirstResult($offset)        // L'équivalent du OFFSET en SQL
            ->setMaxResults($limit)          // L'équivalent du LIMIT en SQL
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre TOTAL de commandes d'un utilisateur pour calculer le nombre de pages
     */
    public function countByUser($user): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
