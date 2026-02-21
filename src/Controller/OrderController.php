<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    /**
     * Route permettant de transformer le panier en session en commande SQL.
     */
    #[Route('/create', name: 'app_order_create')]
    public function create(CartService $cartService, EntityManagerInterface $entityManager): Response
    {
        // 1. Barrière de sécurité : l'utilisateur DOIT être connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Récupération du panier
        $cart = $cartService->getFullCart();
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_products');
        }

        // 3. Création de la commande globale
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('VALIDATED'); // Statut par défaut

        $total = 0;

        // 4. Boucle sur le panier pour créer les lignes de commande (OrderItems)
        foreach ($cart as $item) {
            $orderItem = new OrderItem();

            // On historise les données du produit au moment de l'achat (Snapshot)
            $orderItem->setProductName($item['product']->getName());
            $orderItem->setProductPrice($item['product']->getPrice());
            $orderItem->setQuantity($item['quantity']);

            // On lie la ligne à la commande mère
            $orderItem->setOrderRef($order);

            // On demande à Doctrine de préparer l'insertion de cette ligne
            $entityManager->persist($orderItem);

            // Calcul incrémental du total
            $total += ($item['product']->getPrice() * $item['quantity']);
        }

        // 5. Mise à jour du total de la commande mère et persistance
        $order->setTotal($total);
        $entityManager->persist($order);

        // 6. Transaction finale vers la base de données (Le fameux COMMIT)
        $entityManager->flush();

        // 7. On vide le panier en session
        $cartService->clear();

        // 8. On redirigera vers le futur espace personnel (Profil)
        $this->addFlash('success', 'Commande validée avec succès !');
        return $this->redirectToRoute('app_user_profile');
    }
}
