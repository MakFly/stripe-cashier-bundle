<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Product;
use App\Repository\ProductRepository;

class ProductProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Product
    {
        if ($operation instanceof Get && isset($uriVariables['slug'])) {
            return $this->productRepository->findBySlug($uriVariables['slug']);
        }

        return null;
    }
}
