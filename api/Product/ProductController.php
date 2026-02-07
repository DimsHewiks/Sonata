<?php
namespace Api\Product;

use Sonata\Framework\Attributes\Controller;
use Sonata\Framework\Attributes\Route;

#[Controller('/api/products')]
class ProductController
{
    #[Route('/list', 'GET')]
    public function list(): array
    {
        return ['products' => ['item1', 'item2']];
    }
}