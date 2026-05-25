<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Service\PopularityCalculator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public const SORT_POPULAR = 'popular';
    public const SORT_PURCHASES = 'purchases';
    public const SORT_VIEWS = 'views';
    public const SORT_PRICE_ASC = 'price_asc';
    public const SORT_PRICE_DESC = 'price_desc';

    public const ALLOWED_SORTS = [
        self::SORT_POPULAR,
        self::SORT_PURCHASES,
        self::SORT_VIEWS,
        self::SORT_PRICE_ASC,
        self::SORT_PRICE_DESC,
    ];

    public function __construct(
        ManagerRegistry $registry,
        private PopularityCalculator $popularityCalculator,
    ) {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findByPopularity(string $sort = self::SORT_POPULAR, ?Category $category = null): array
    {
        if (!in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = self::SORT_POPULAR;
        }

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.stats', 's')
            ->addSelect('s')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        if ($category !== null) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        $products = $qb->getQuery()->getResult();

        usort($products, function (Product $a, Product $b) use ($sort): int {
            return match ($sort) {
                self::SORT_PRICE_ASC => $a->getPrice() <=> $b->getPrice(),
                self::SORT_PRICE_DESC => $b->getPrice() <=> $a->getPrice(),
                self::SORT_VIEWS => ($b->getStats()?->getViewCount() ?? 0) <=> ($a->getStats()?->getViewCount() ?? 0),
                self::SORT_PURCHASES => ($b->getStats()?->getPurchaseCount() ?? 0) <=> ($a->getStats()?->getPurchaseCount() ?? 0),
                default => $this->popularityCalculator->calculate($b->getStats())
                    <=> $this->popularityCalculator->calculate($a->getStats()),
            };
        });

        return $products;
    }
}
