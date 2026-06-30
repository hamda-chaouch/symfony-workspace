<?php

namespace App\Controller\Ibuy;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/ibuy', name: 'app_ibuy_home')]
    public function index(): Response
    {
        return $this->render('ibuy/home/index.html.twig'); //, [
//            'controller_name' => 'Ibuy/HomeController',
//        ]);
    }
}
