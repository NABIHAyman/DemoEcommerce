<?php

namespace App\Cart;

use App\DTO\CartDTO;
use App\DTO\CartItemDTO;

interface CartInterface
{
    public function add(CartItemDTO $item, CartDTO $cart): CartDTO;
    public function remove(CartItemDTO $item, CartDTO $cart): CartDTO;
    public function getCart(string $identifier = 'cart'): CartDTO;
    public function clearCart(string $identifier = 'cart'): void;
}
