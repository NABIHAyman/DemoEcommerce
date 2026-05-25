<?php

namespace App\Entity;

use App\Repository\ProductStatsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductStatsRepository::class)]
class ProductStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'stats', targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $viewCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $addToCartCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $purchaseCount = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function incrementViewCount(): static
    {
        ++$this->viewCount;
        $this->touch();

        return $this;
    }

    public function getAddToCartCount(): int
    {
        return $this->addToCartCount;
    }

    public function incrementAddToCartCount(): static
    {
        ++$this->addToCartCount;
        $this->touch();

        return $this;
    }

    public function getPurchaseCount(): int
    {
        return $this->purchaseCount;
    }

    public function incrementPurchaseCount(int $quantity = 1): static
    {
        $this->purchaseCount += max(1, $quantity);
        $this->touch();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Initialisation des compteurs (fixtures / tests uniquement).
     */
    public function seedCounts(int $views, int $addToCart, int $purchases): static
    {
        $this->viewCount = $views;
        $this->addToCartCount = $addToCart;
        $this->purchaseCount = $purchases;
        $this->touch();

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
