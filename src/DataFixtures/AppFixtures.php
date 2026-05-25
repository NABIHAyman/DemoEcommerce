<?php

namespace App\DataFixtures;

use App\Entity\Carrier;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductStats;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Stats seed : score popularité = vues×1 + panier×3 + achats×10
 * Ordre attendu "Top Populaires" : Headphones > Laptop > Macbook > Smart Watch > ...
 */
class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
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

        foreach (
            [
                ['Standard Delivery', 'Livraison à domicile en 3 à 5 jours ouvrés.', 500],
                ['Express 24h', 'Livraison rapide le lendemain avant 13h.', 1200],
                ['Relais Colis', 'Livraison dans le point relais le plus proche.', 390],
                ['Retrait Magasin', 'Récupérez votre commande gratuitement en 2h.', 0],
            ] as $data
        ) {
            $carrier = new Carrier();
            $carrier->setName($data[0])->setDescription($data[1])->setPrice($data[2]);
            $manager->persist($carrier);
        }

        $categories = [];
        foreach (['IT & Computers', 'Home Appliances', 'Audio & Hi-Fi', 'Gadgets'] as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        // name, description, price (cents), isTop, catIdx, views, addToCart, purchases
        $productsData = [
            ['Wireless Headphones', 'Active Noise Cancelling technology.', 25000, true, 2, 320, 95, 42],
            ['Gaming Laptop', 'High performance for pro gamers.', 150000, true, 0, 180, 55, 22],
            ['Macbook Air', 'M3 Chip, 16GB RAM, Midnight color.', 129900, true, 0, 150, 40, 15],
            ['Smart Watch', 'Track your health and fitness 24/7.', 19900, false, 3, 110, 35, 12],
            ['Air Fryer', 'Healthy cooking with 90% less oil.', 12000, true, 1, 70, 22, 7],
            ['Coffee Machine', 'Perfect espresso every morning.', 8999, false, 1, 55, 18, 6],
            ['Bluetooth Speaker', 'Waterproof IP67 for pool parties.', 5500, false, 2, 25, 8, 2],
            ['Power Bank 20k', 'Charge your phone 5 times.', 3500, false, 3, 10, 3, 1],
        ];

        foreach ($productsData as $data) {
            $product = new Product();
            $product->setName($data[0])
                ->setDescription($data[1])
                ->setPrice($data[2])
                ->setIsTop($data[3])
                ->setCategory($categories[$data[4]]);

            $stats = new ProductStats();
            $stats->setProduct($product);
            $stats->seedCounts($data[5], $data[6], $data[7]);
            $product->setStats($stats);

            $manager->persist($product);
            $manager->persist($stats);
        }

        $manager->flush();
    }
}
