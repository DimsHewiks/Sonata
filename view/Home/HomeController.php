<?php
namespace View\Home;

use Core\Attributes\Controller;
use Core\Attributes\Route;
use View\Home\Pages\HomePage;


#[Controller(prefix: '/')]
class HomeController
{
    #[Route(path: '/', method: 'GET')]
    public function test(): string
    {
        return new HomePage()->home();
    }

    #[Route(path: '/about', method: 'GET')]
    public function about():string
    {
        return '<h2>УВА Молодец!!!</h2>';
    }


}