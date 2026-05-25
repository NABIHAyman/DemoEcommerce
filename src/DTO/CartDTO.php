<?php

namespace App\DTO;
use App\DTO\CartItemDTO;

class CartDTO
{
    /** @var CartItemDTO[] */
    public array $items = [];

    // Méthode calculant le total du panier
    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getSubtotal();
        }
        return $total;
    }
}
