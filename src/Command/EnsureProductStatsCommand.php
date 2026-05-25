<?php

namespace App\Command;

use App\Entity\ProductStats;
use App\Repository\ProductRepository;
use App\Repository\ProductStatsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:product-stats:ensure',
    description: 'Crée les lignes product_stats manquantes (sans effacer la base)',
)]
class EnsureProductStatsCommand extends Command
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductStatsRepository $productStatsRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        foreach ($this->productRepository->findAll() as $product) {
            if ($this->productStatsRepository->findOneByProduct($product) !== null) {
                continue;
            }

            $stats = new ProductStats();
            $stats->setProduct($product);
            $product->setStats($stats);
            $this->entityManager->persist($stats);
            ++$created;
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            '%d produit(s) au total — %d ligne(s) product_stats créée(s).',
            count($this->productRepository->findAll()),
            $created
        ));

        return Command::SUCCESS;
    }
}
