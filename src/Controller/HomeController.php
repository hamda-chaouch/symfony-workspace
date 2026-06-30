<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
//use Symfony\Component\Yaml\Yaml;

final class HomeController extends AbstractController
{
   #[Route('/', name: 'home_index')]
   #[Route('/home', name: 'home')]
    public function index(): Response
    {
        //$projects = Yaml::parseFile(__DIR__.'/../../config/projects.yaml')['projects'];
        return $this->render('home/index.html.twig'); //, [
        //       'projects' => $projects,
        //]);
    }
}
