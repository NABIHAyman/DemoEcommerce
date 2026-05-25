<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\AddressRepository;
use App\Repository\CarrierRepository;
use App\Cart\CartHandler;
use App\Service\ProductStatsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    /**
     * 1. ÉTAPE DE VÉRIFICATION ET AFFICHAGE DU CHECKOUT
     */
    #[Route('/', name: 'app_checkout')]
    public function index(CartHandler $cartHandler, CarrierRepository $carrierRepository): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $cart = $cartHandler->getCart();
        if (empty($cart->items)) return $this->redirectToRoute('app_products');

        // Règle métier : Si le client n'a aucune adresse, on le force à en créer une d'abord
        if (count($user->getAddresses()) === 0) {
            $this->addFlash('warning', 'Veuillez ajouter une adresse de livraison pour continuer.');
            return $this->redirectToRoute('app_checkout_address_add');
        }

        // ancienne logique (Cart était un tableau &items)
        /*
        // Calcul du total du panier (sans livraison)
        $totalCart = 0;
        foreach ($cart as $item) {
            $totalCart += $item['product']->getPrice() * $item['quantity'];
        }
        */

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'totalCart' => $cart->getTotal(), // Utilisation directe du DTO
            'addresses' => $user->getAddresses(),
            'carriers' => $carrierRepository->findAll()
        ]);
    }

    /**
     * 2. AJOUT D'UNE ADRESSE (Fait "Maison" via POST)
     */
    #[Route('/address/add', name: 'app_checkout_address_add')]
    public function addAddress(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $address = new Address();
            $address->setUser($user);
            $address->setName($request->request->get('name')); // Ex: "Domicile"
            $address->setFirstname($request->request->get('firstname'));
            $address->setLastname($request->request->get('lastname'));
            $address->setStreet($request->request->get('street'));
            $address->setCity($request->request->get('city'));
            $address->setPostalCode($request->request->get('postalCode'));
            $address->setCountry($request->request->get('country'));

            $entityManager->persist($address);
            $entityManager->flush();

            $this->addFlash('success', 'Adresse enregistrée avec succès !');
            return $this->redirectToRoute('app_checkout');
        }

        return $this->render('checkout/address_add.html.twig');
    }

    /**
     * 3. TRAITEMENT DU PAIEMENT ET CRÉATION DE LA COMMANDE
     */
    #[Route('/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(
        Request $request,
        CartHandler $cartHandler,
        AddressRepository $addressRepository,
        CarrierRepository $carrierRepository,
        EntityManagerInterface $entityManager,
        ProductStatsManager $productStatsManager,
    ): Response {
        $user = $this->getUser();
        $cart = $cartHandler->getCart();
        if (empty($cart->items)) return $this->redirectToRoute('app_products');

        // Récupération des choix de l'utilisateur depuis le formulaire HTML
        $addressId = $request->request->get('address_id');
        $carrierId = $request->request->get('carrier_id');

        $address = $addressRepository->find($addressId);
        $carrier = $carrierRepository->find($carrierId);

        if (!$address || !$carrier || empty($cart)) {
            $this->addFlash('error', 'Une erreur est survenue lors de la validation.');
            return $this->redirectToRoute('app_checkout');
        }

        // Création de l'entité Order
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('VALIDATED');

        // LE FAMEUX SNAPSHOT : On fige l'adresse et le transporteur en texte brut !
        $order->setDeliveryAddress($address->getFormattedAddress());
        $order->setBillingAddress($address->getFormattedAddress()); // Pour simplifier, on prend la même
        $order->setCarrierName($carrier->getName());
        $order->setCarrierPrice($carrier->getPrice());

        $totalOrder = 0;
        $purchaseStats = [];

        foreach ($cart->items as $item) {
            $totalOrder += $item->getSubtotal();
            $purchaseStats[] = ['product' => $item->product, 'quantity' => $item->quantity];
        }

        $order->setTotal($totalOrder + $carrier->getPrice());
        $entityManager->persist($order);

        foreach ($cart->items as $item) {
            $orderItem = new OrderItem();
            $orderItem->setProductName($item->product->getName());
            $orderItem->setProductPrice($item->product->getPrice());
            $orderItem->setQuantity($item->quantity);
            $orderItem->setOrderRef($order);

            $entityManager->persist($orderItem);
        }

        $entityManager->flush();

        // Stats après flush : évite de flusher un Order encore "new" dans l'UnitOfWork
        $productStatsManager->recordPurchases($purchaseStats);

        $cartHandler->clear();

        $this->addFlash('success', 'Commande #' . $order->getId() . ' confirmée avec succès !');
        return $this->redirectToRoute('app_profile_order_show', ['id' => $order->getId()]);
    }
}
