<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    // Pattern d'Injection de Dépendance (Dependency Injection - DI).
    // Au lieu d'instancier manuellement le service de hachage (couplage fort),
    // on demande au conteneur de services (Service Container) de Symfony de nous l'injecter
    // automatiquement lors de l'instanciation de cette classe (Autowiring).
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Méthode principale exécutée par la commande CLI `doctrine:fixtures:load`.
    // Elle orchestre le peuplement (Data Seeding) de la base de données de développement.
    public function load(ObjectManager $manager): void
    {
        // 1. Initialisation de l'utilisateur Administrateur (Super-user).
        $admin = new User();
        $admin->setEmail('admin@ecommerce.com');

        // Attribution des droits via un tableau de rôles (Role-Based Access Control - RBAC).
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setFirstname('Ayman');
        $admin->setLastname('Admin');

        // Sécurité : Application de l'algorithme de hachage (défini dans security.yaml, ex: Argon2id)
        // sur le mot de passe en clair avant de l'injecter dans l'entité.
        $password = $this->hasher->hashPassword($admin, 'Admin123!');
        $admin->setPassword($password);

        // Le pattern "Unit of Work" de Doctrine entre en action.
        // L'ObjectManager commence à "traquer" cet objet en mémoire. Aucune requête SQL n'est encore exécutée.
        $manager->persist($admin);

        // 2. Création du référentiel (Lookup tables) : Les Catégories.
        $catComputers = new Category();
        $catComputers->setName('IT & Computers');
        $manager->persist($catComputers);

        $catAppliances = new Category();
        $catAppliances->setName('Home Appliances');
        $manager->persist($catAppliances);

        // 3. Création du jeu d'essai métier principal : Les Produits.
        $product1 = new Product();
        $product1->setName('Gaming Laptop');
        $product1->setDescription('A highly powerful laptop for gaming.');

        // Modélisation financière (Floating Point Math workaround).
        // Stockage strict en centimes (Integer) pour prévenir la perte de précision flottante des CPU.
        $product1->setPrice(150000); // Équivaut à 1500.00 € / $
        $product1->setIsTop(true);

        // Mapping de la relation (Foreign Key) grâce à l'ORM Doctrine.
        $product1->setCategory($catComputers);
        $manager->persist($product1);

        $product2 = new Product();
        $product2->setName('Coffee Machine');
        $product2->setDescription('For those tough mornings.');
        $product2->setPrice(8999); // Équivaut à 89.99 € / $
        $product2->setIsTop(false);
        $product2->setCategory($catAppliances);
        $manager->persist($product2);

        // 4. Commit de la transaction de base de données.
        // Doctrine calcule le "diff" (changement d'état) de tous les objets persistés en mémoire,
        // génère le code SQL optimal (INSERT INTO...), et l'envoie au serveur MySQL en un seul batch.
        $manager->flush();
    }
}
