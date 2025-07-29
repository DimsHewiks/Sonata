<?php
namespace Api\Product;

use Core\Attributes\Controller;
use Core\Attributes\Route;

#[Controller('/api/products')]
class ProductController
{
    #[Route('/list', 'GET')]
    public function list(): array
    {
        return ['products' => ['item1', 'item2']];
    }
}