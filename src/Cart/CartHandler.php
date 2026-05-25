<?php

namespace App\Cart;

use App\DTO\CartDTO;
use App\DTO\CartItemDTO;
use App\Entity\Product;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CartHandler
{
    public function __construct(
        #[Autowire(service: SessionCart::class)]
        private CartInterface $strategy
    ) {}

    public function getCart(): CartDTO
    {
        return $this->strategy->getCart();
    }

    public function addProduct(Product $product): void
    {
        $item = new CartItemDTO($product);
        $cart = $this->getCart();
        $this->strategy->add($item, $cart);
    }

    public function removeProduct(Product $product): void
    {
        $item = new CartItemDTO($product);
        $cart = $this->getCart();
        $this->strategy->remove($item, $cart);
    }

    public function clear(): void
    {
        $this->strategy->clearCart();
    }
}
