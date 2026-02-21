<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;

class ProductController extends AbstractController
{
    // Définition de la route d'accès au catalogue.
    // Note architecturale : Cette route est protégée en amont par la règle d'access_control (^/products)
    // définie dans notre security.yaml. Si un utilisateur non authentifié tente d'y accéder,
    // le pare-feu intercepte la requête avant même l'exécution de ce contrôleur.
    #[Route('/products', name: 'app_products')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        // Récupération de l'entité User courante depuis le contexte de sécurité de Symfony.
        // Puisque nous sommes dans une architecture "Stateful" (avec sessions), Symfony lit le cookie de session,
        // valide l'utilisateur en base de données, et nous retourne l'objet User parfaitement hydraté.
        //$user = $this->getUser(); // twig pourra y accéder via une variable super globale

        // Utilisation de l'injection de dépendance (Autowiring) de Symfony pour obtenir le ProductRepository.
        // Le repository agit comme notre couche d'accès aux données (Data Access Layer).
        // La méthode findAll() demande à Doctrine ORM d'exécuter un "SELECT * FROM product" de manière optimisée
        // et de mapper les résultats en un tableau d'objets (Entities) Product.
        $products = $productRepository->findAll();
        $categories = $categoryRepository->findAll();

        // Délégation de la couche présentation au moteur de templating Twig.
        // On passe un tableau associatif (le contexte) contenant notre liste d'objets produits
        // et l'objet utilisateur courant pour dynamiser l'affichage (ex: afficher le nom de l'utilisateur).
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories, // On passe toutes les catégories pour construire le menu
            'current_category' => null, // Permet à la vue de savoir qu'on ne filtre pas
        ]);
    }

    /**
     * Route affichant les détails d'un produit spécifique.
     * * Concept architectural (ParamConverter / Entity Value Resolver) :
     * Symfony voit le paramètre {id} dans l'URL et le type 'Product' dans les arguments.
     * Il va automatiquement faire un "SELECT * FROM product WHERE id = {id}"
     * et nous injecter l'objet directement. Si l'ID n'existe pas, il génère une erreur 404 tout seul !
     */
    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * Route de filtrage : Affiche uniquement les produits d'une catégorie spécifique.
     * Le ParamConverter de Symfony transforme le {id} de l'URL directement en objet Category !
     */
    #[Route('/products/category/{id}', name: 'app_products_by_category')]
    public function category(Category $category, CategoryRepository $categoryRepository): Response
    {
        return $this->render('product/index.html.twig', [
            // Magie de l'ORM : On navigue la relation inverse (OneToMany) pour récupérer les produits
            'products' => $category->getProducts(),
            'categories' => $categoryRepository->findAll(),
            'current_category' => $category, // On passe la catégorie filtrée pour la mettre en surbrillance
        ]);
    }
}
