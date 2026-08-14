<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

final class ServicesController extends AbstractController
{
    #[Route('/services', name: 'app_services')]
    public function index(): Response
    {
        $services = Yaml::parseFile(__DIR__.'/../../config/services_list.yaml')['services'];

        return $this->render('services/index.html.twig', [
            'services' => $services,
        ]);
    }
}
