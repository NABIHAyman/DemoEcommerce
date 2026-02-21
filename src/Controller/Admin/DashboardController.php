<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository
    ): Response {
        // Récupération de quelques statistiques pour le tableau de bord
        $totalOrders = $orderRepository->count([]);
        $totalProducts = $productRepository->count([]);
        $totalUsers = $userRepository->count([]);

        // Calcul du chiffre d'affaires total (optionnel mais sympa)
        // On récupère toutes les commandes Validées/Livrées
        $orders = $orderRepository->findAll();
        $revenue = 0;
        foreach ($orders as $order) {
            $revenue += $order->getTotal();
        }

        return $this->render('admin/dashboard.html.twig', [
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_users' => $totalUsers,
            'revenue' => $revenue,
            'recent_orders' => $orderRepository->findBy([], ['createdAt' => 'DESC'], 5) // Les 5 dernières
        ]);
    }
}
