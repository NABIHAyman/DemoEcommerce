<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Carrier; // Ne pas oublier l'import !
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // --- 1. LES UTILISATEURS ---
        $admin = new User();
        $admin->setEmail('admin@ecommerce.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setFirstname('Ayman')
            ->setLastname('Admin')
            ->setPassword($this->hasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        $customer = new User();
        $customer->setEmail('client@test.com')
            ->setRoles(['ROLE_USER'])
            ->setFirstname('Jean')
            ->setLastname('Dupont')
            ->setPassword($this->hasher->hashPassword($customer, 'Client123!'));
        $manager->persist($customer);

        // --- 2. LES TRANSPORTEURS (CARRIERS) ---
        $carriersData = [
            ['Standard Delivery', 'Livraison à domicile en 3 à 5 jours ouvrés.', 500],
            ['Express 24h', 'Livraison rapide le lendemain avant 13h.', 1200],
            ['Relais Colis', 'Livraison dans le point relais le plus proche.', 390],
            ['Retrait Magasin', 'Récupérez votre commande gratuitement en 2h.', 0],
        ];

        foreach ($carriersData as $data) {
            $carrier = new Carrier();
            $carrier->setName($data[0])
                ->setDescription($data[1])
                ->setPrice($data[2]);
            $manager->persist($carrier);
        }

        // --- 3. LES CATÉGORIES ---
        $categories = [];
        $catNames = ['IT & Computers', 'Home Appliances', 'Audio & Hi-Fi', 'Gadgets'];

        foreach ($catNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category; // On stocke pour lier aux produits
        }

        // --- 4. LES PRODUITS (Gros jeu de données) ---
        $productsData = [
            ['Gaming Laptop', 'High performance for pro gamers.', 150000, true, 0],
            ['Macbook Air', 'M3 Chip, 16GB RAM, Midnight color.', 129900, true, 0],
            ['Coffee Machine', 'Perfect espresso every morning.', 8999, false, 1],
            ['Air Fryer', 'Healthy cooking with 90% less oil.', 12000, true, 1],
            ['Wireless Headphones', 'Active Noise Cancelling technology.', 25000, true, 2],
            ['Bluetooth Speaker', 'Waterproof IP67 for pool parties.', 5500, false, 2],
            ['Smart Watch', 'Track your health and fitness 24/7.', 19900, false, 3],
            ['Power Bank 20k', 'Charge your phone 5 times.', 3500, false, 3],
        ];

        foreach ($productsData as $data) {
            $product = new Product();
            $product->setName($data[0])
                ->setDescription($data[1])
                ->setPrice($data[2])
                ->setIsTop($data[3])
                ->setCategory($categories[$data[4]]); // Lien dynamique
            $manager->persist($product);
        }

        $manager->flush();
    }
}
