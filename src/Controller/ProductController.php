<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductStatsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        return $this->renderProductListing($request, $productRepository, $categoryRepository);
    }

    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(Product $product, ProductStatsManager $productStatsManager): Response
    {
        $productStatsManager->recordView($product);

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/products/category/{id}', name: 'app_products_by_category')]
    public function category(
        Request $request,
        Category $category,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        return $this->renderProductListing($request, $productRepository, $categoryRepository, $category);
    }

    #[Route('/categories', name: 'app_browse_categories')]
    public function browseCategories(CategoryRepository $categoryRepository): Response
    {
        return $this->render('product/browse_categories.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    private function renderProductListing(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ?Category $currentCategory = null,
    ): Response {
        $sort = $request->query->getString('sort', ProductRepository::SORT_POPULAR);
        if (!in_array($sort, ProductRepository::ALLOWED_SORTS, true)) {
            $sort = ProductRepository::SORT_POPULAR;
        }

        $products = $productRepository->findByPopularity($sort, $currentCategory);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'current_category' => $currentCategory,
            'current_sort' => $sort,
        ]);
    }
}
