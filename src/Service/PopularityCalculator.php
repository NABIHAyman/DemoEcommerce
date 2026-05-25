<?php

namespace App\Service;

use App\Entity\ProductStats;

/**
 * Computes a weighted popularity score from product statistics.
 * Weights: views (1), add-to-cart (3), purchases (10).
 */
class PopularityCalculator
{
    public const WEIGHT_VIEW = 1;
    public const WEIGHT_ADD_TO_CART = 3;
    public const WEIGHT_PURCHASE = 10;

    public function calculate(?ProductStats $stats): int
    {
        if ($stats === null) {
            return 0;
        }

        return ($stats->getViewCount() * self::WEIGHT_VIEW)
            + ($stats->getAddToCartCount() * self::WEIGHT_ADD_TO_CART)
            + ($stats->getPurchaseCount() * self::WEIGHT_PURCHASE);
    }

    public function getDqlScoreExpression(string $statsAlias = 's'): string
    {
        return sprintf(
            '(%s * %d + %s * %d + %s * %d)',
            $this->getDqlNullSafeField($statsAlias, 'viewCount'),
            self::WEIGHT_VIEW,
            $this->getDqlNullSafeField($statsAlias, 'addToCartCount'),
            self::WEIGHT_ADD_TO_CART,
            $this->getDqlNullSafeField($statsAlias, 'purchaseCount'),
            self::WEIGHT_PURCHASE
        );
    }

    /** DQL-compatible null-safe counter (COALESCE is not available in Doctrine DQL). */
    public function getDqlNullSafeField(string $alias, string $field): string
    {
        return sprintf(
            'CASE WHEN %1$s.%2$s IS NULL THEN 0 ELSE %1$s.%2$s END',
            $alias,
            $field
        );
    }
}
