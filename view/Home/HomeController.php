<?php
namespace View\Home;

use Core\Attributes\Controller;
use Core\Attributes\Route;
use Core\Attributes\Tag;
use Core\Http\Response;
use View\Home\Pages\HomePage;


#[Controller(prefix: '/')]
#[Tag('Статика')]
class HomeController
{
    #[Route(path: '/', method: 'GET', summary: 'Главная страница')]
    public function home(): never
    {
        $html = new HomePage()->home();
        Response::html($html, 200);
    }

    #[Route(path: '/about', method: 'GET', summary: 'тестовая страница')]
    public function about(): never
    {
        Response::html('<h2>УВА Молодец!!!</h2>', 200);
    }


}