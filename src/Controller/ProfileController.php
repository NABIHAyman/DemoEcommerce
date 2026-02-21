<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Order;

class ProfileController extends AbstractController
{
    /**
     * Route de l'espace personnel (Tableau de bord de l'utilisateur).
     */
    #[Route('/profile', name: 'app_user_profile')]
    public function index(): Response
    {
        // 1. Barrière de sécurité : On s'assure que seul un utilisateur authentifié accède ici.
        $user = $this->getUser();

        if (!$user) {
            // Si la session a expiré ou que c'est un visiteur anonyme, on le renvoie au login.
            return $this->redirectToRoute('app_login');
        }

        // 2. Rendu de la vue.
        // On passe simplement l'objet User. Doctrine s'occupera de charger la collection 'orders' à la volée.
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Route affichant les détails d'une commande spécifique.
     */
    #[Route('/profile/order/{id}', name: 'app_profile_order_show')]
    public function showOrder(Order $order): Response
    {
        // BARRIÈRE DE SÉCURITÉ CRITIQUE (IDOR Protection) :
        // On s'assure que la commande demandée dans l'URL appartient bien à l'utilisateur connecté.
        // Si le Client A essaie de taper l'ID de la commande du Client B, on bloque l'accès.
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas l\'autorisation de voir cette commande.');
        }

        return $this->render('profile/order_show.html.twig', [
            'order' => $order,
        ]);
    }
}
