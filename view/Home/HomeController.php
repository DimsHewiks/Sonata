<?php
namespace View\Home;

use Core\Attributes\Controller;
use Core\Attributes\Route;



#[Controller(prefix: '/')]
class HomeController
{
    #[Route(path: '/', method: 'GET')]
    public function test():string
    {
        return '<h2>Home page</h2>';
    }

    #[Route(path: '/about', method: 'GET')]
    public function about():string
    {
        return '<h2>УВА Молодец!!!</h2>';
    }


}