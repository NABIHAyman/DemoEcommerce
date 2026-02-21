<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    // Injection des dépendances via le constructeur (PHP 8 Property Promotion)
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {
    }

    /**
     * Ajoute un produit au panier en session.
     */
    public function add(int $id): void
    {
        // On accède à la session actuelle via le RequestStack
        $session = $this->requestStack->getSession();

        // On récupère le panier actuel, ou un tableau vide s'il n'existe pas encore
        $cart = $session->get('cart', []);

        // Si le produit est déjà dans le panier, on incrémente la quantité
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            // Sinon, on l'ajoute avec une quantité de 1
            $cart[$id] = 1;
        }

        // On sauvegarde le panier mis à jour dans la session
        $session->set('cart', $cart);
    }

    /**
     * Récupère le panier "hydraté" (avec les vrais objets Product de la base de données).
     */
    public function getFullCart(): array
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        $fullCart = [];

        // On boucle sur notre tableau de session [id => quantite]
        foreach ($cart as $id => $quantity) {
            // On interroge MySQL pour récupérer le vrai produit
            $product = $this->productRepository->find($id);

            // Si le produit existe bien en base, on l'ajoute au résultat
            if ($product) {
                $fullCart[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
            } else {
                // Auto-nettoyage : si le produit a été supprimé de la base entre temps, on l'enlève du panier
                $this->remove($id);
            }
        }

        return $fullCart;
    }

    /**
     * Supprime totalement une ligne du panier.
     */
    public function remove(int $id): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
    }

    /**
     * Vide complètement le panier après une commande validée.
     */
    public function clear(): void
    {
        $this->requestStack->getSession()->remove('cart');
    }
}
