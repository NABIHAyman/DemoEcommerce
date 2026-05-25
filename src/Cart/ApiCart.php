<?php

namespace App\Cart;

use App\DTO\CartDTO;
use App\DTO\CartItemDTO;

/**
 * ApiCart — Stub implementation of CartInterface via an external API.
 * Demonstrates the Open/Closed principle: CartHandler is not modified.
 * Methods contain dd() stubs to simulate API behavior.
 */
class ApiCart implements CartInterface
{
    public function add(CartItemDTO $item, CartDTO $cart): CartDTO
    {
        // Simulated API call: POST /api/cart/add
        // dd(['action' => 'add', 'product_id' => $item->product->getId(), 'quantity' => $item->quantity]);
        return $cart;
    }

    public function remove(CartItemDTO $item, CartDTO $cart): CartDTO
    {
        // Simulated API call: DELETE /api/cart/{productId}
        // dd(['action' => 'remove', 'product_id' => $item->product->getId()]);
        return $cart;
    }

    public function getCart(string $identifier = 'cart'): CartDTO
    {
        // Simulated API call: GET /api/cart/{identifier}
        // dd(['action' => 'getCart', 'identifier' => $identifier]);
        return new CartDTO();
    }

    public function clearCart(string $identifier = 'cart'): void
    {
        // Simulated API call: DELETE /api/cart/{identifier}
        // dd(['action' => 'clearCart', 'identifier' => $identifier]);
    }
}
