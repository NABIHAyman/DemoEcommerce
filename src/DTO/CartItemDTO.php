<?php

namespace App\DTO;

use App\Entity\Product;

class CartItemDTO
{
    public function __construct(
        public Product $product,
        public int $quantity = 1
    ) {}

    // Méthode utilitaire pour calculer le sous-total de cette ligne
    public function getSubtotal(): int
    {
        return $this->product->getPrice() * $this->quantity;
    }
}
