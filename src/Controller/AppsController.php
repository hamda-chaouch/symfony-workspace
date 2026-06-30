<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

final class AppsController extends AbstractController
{
    #[Route('/apps', name: 'app_apps')]
    public function index(): Response
    {
        $projects = Yaml::parseFile(__DIR__.'/../../config/projects.yaml')['projects'];
        return $this->render('apps/index.html.twig', [
            'projects' => $projects,
        ]);
    }
}
