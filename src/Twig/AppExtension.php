<?php

namespace App\Twig;

use App\Cart\CartHandler;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private CartHandler $cartHandler)
    {
    }

    public function getGlobals(): array
    {
        $cart = $this->cartHandler->getCart();
        $count = 0;
        foreach ($cart->items as $item) {
            $count += $item->quantity;
        }

        return [
            'cart_count' => $count,
        ];
    }
}
