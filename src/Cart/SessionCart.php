<?php

namespace App\Cart;

use App\DTO\CartDTO;
use App\DTO\CartItemDTO;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionCart implements CartInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {}

    public function getCart(string $identifier = 'cart'): CartDTO
    {
        $session = $this->requestStack->getSession();
        $cartData = $session->get($identifier, []);

        $cart = new CartDTO();

        foreach ($cartData as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $cart->items[] = new CartItemDTO($product, $quantity);
            }
        }

        return $cart;
    }

    public function add(CartItemDTO $item, CartDTO $cart): CartDTO
    {
        $session = $this->requestStack->getSession();
        $cartData = $session->get('cart', []);
        $productId = $item->product->getId();

        if (isset($cartData[$productId])) {
            $cartData[$productId]++;
        } else {
            $cartData[$productId] = 1;
        }

        $session->set('cart', $cartData);
        return $this->getCart(); // Retourne le panier mis à jour
    }

    public function remove(CartItemDTO $item, CartDTO $cart): CartDTO
    {
        $session = $this->requestStack->getSession();
        $cartData = $session->get('cart', []);
        $productId = $item->product->getId();

        if (isset($cartData[$productId])) {
            unset($cartData[$productId]);
        }

        $session->set('cart', $cartData);
        return $this->getCart();
    }

    public function clearCart(string $identifier = 'cart'): void
    {
        $this->requestStack->getSession()->remove($identifier);
    }
}
