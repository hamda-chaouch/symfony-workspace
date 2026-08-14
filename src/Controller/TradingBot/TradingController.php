<?php

namespace App\Controller\TradingBot;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TradingController extends AbstractController
{
    #[Route('/trading', name: 'trading_hub')]
    public function index(): Response
    {
        return $this->render('trading_bot/index.html.twig');
    }
}
