<?php

namespace App\Controller;

use App\Cart\CartHandler;
use App\Entity\Product;
use App\Service\ProductStatsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartHandler $cartHandler,
        private ProductStatsManager $productStatsManager,
    ) {
    }

    /**
     * Route principale : Affiche le contenu du panier.
     */
    #[Route('/', name: 'app_cart_index')]
    public function index(): Response
    {
        // On récupère le panier hydraté (avec les vrais objets Product)
        $cart = $this->cartHandler->getCart();

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $cart->getTotal(),
        ]);
    }

    /**
     * Route d'action : Ajoute un produit et redirige vers le panier.
     */
    #[Route('/add/{id}', name: 'app_cart_add')]
    public function add(Product $product): Response
    {
        $this->cartHandler->addProduct($product);
        $this->productStatsManager->recordAddToCart($product);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Route d'action : Supprime une ligne du panier.
     */
    #[Route('/remove/{id}', name: 'app_cart_remove')]
    public function remove(Product $product): Response
    {
        $this->cartHandler->removeProduct($product);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Vide complètement le panier après une commande validée.
     */
    public function clear(): void
    {
        $this->cartHandler->clear();
    }
}
