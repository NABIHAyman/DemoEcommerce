<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    // Injection du service métier via le constructeur
    public function __construct(
        private CartService $cartService
    ) {
    }

    /**
     * Route principale : Affiche le contenu du panier.
     */
    #[Route('/', name: 'app_cart_index')]
    public function index(): Response
    {
        // On récupère le panier hydraté (avec les vrais objets Product) depuis notre service
        $cart = $this->cartService->getFullCart();

        // Calcul du total général du panier
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['product']->getPrice() * $item['quantity'];
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    /**
     * Route d'action : Ajoute un produit et redirige vers le panier.
     */
    #[Route('/add/{id}', name: 'app_cart_add')]
    public function add(int $id): Response
    {
        // On délègue l'ajout à notre service métier
        $this->cartService->add($id);

        // Consigne du professeur : "une fois le produit ajouté, l’utilisateur est redirigé vers cette page (le panier)"
        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Route d'action : Supprime une ligne du panier.
     */
    #[Route('/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id): Response
    {
        $this->cartService->remove($id);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Vide complètement le panier après une commande validée.
     */
    public function clear(): void
    {
        $this->requestStack->getSession()->remove('cart');
    }
}
