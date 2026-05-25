<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductStats;
use App\Repository\ProductStatsRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductStatsManager
{
    public function __construct(
        private ProductStatsRepository $productStatsRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function recordView(Product $product): void
    {
        $this->getOrCreateStats($product)->incrementViewCount();
        $this->entityManager->flush();
    }

    public function recordAddToCart(Product $product): void
    {
        $this->getOrCreateStats($product)->incrementAddToCartCount();
        $this->entityManager->flush();
    }

    public function recordPurchase(Product $product, int $quantity = 1): void
    {
        $this->getOrCreateStats($product)->incrementPurchaseCount($quantity);
        $this->entityManager->flush();
    }

    /**
     * @param iterable<array{product: Product, quantity: int}> $items
     */
    public function recordPurchases(iterable $items): void
    {
        foreach ($items as $item) {
            $this->getOrCreateStats($item['product'])->incrementPurchaseCount($item['quantity']);
        }

        $this->entityManager->flush();
    }

    private function getOrCreateStats(Product $product): ProductStats
    {
        $stats = $product->getStats();

        if ($stats === null) {
            $stats = $this->productStatsRepository->findOneByProduct($product);
        }

        if ($stats === null) {
            $stats = new ProductStats();
            $stats->setProduct($product);
            $this->entityManager->persist($stats);
        }

        $product->setStats($stats);

        return $stats;
    }
}
