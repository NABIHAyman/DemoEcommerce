<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductStats>
 */
class ProductStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductStats::class);
    }

    public function findOneByProduct(Product $product): ?ProductStats
    {
        return $this->findOneBy(['product' => $product]);
    }
}
