<?php

namespace App\Controller\VRstudio;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

final class VRstudioController extends AbstractController
{
//    #[Route('/v/rstudio/v/rstudio', name: 'app_v_rstudio_v_rstudio')]
    #[Route('/vr-studio', name: 'app_vr_studio')]
    public function index(): Response
    {
        $projects = Yaml::parseFile(__DIR__.'/../../../config/projects.yaml')['projects'];
        $vrProjects = array_filter($projects, fn($p) => ($p['category'] ?? '') === 'vr');

        return $this->render('v_rstudio/v_rstudio/index.html.twig', [
			'projects' => $vrProjects,
        ]);
    }
}
