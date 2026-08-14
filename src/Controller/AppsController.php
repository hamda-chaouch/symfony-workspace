<?php
/*
 * Copyright (c) 2025 Hamda Chaouch.
 *
 * Licensed under the Apache License, Version 2.0 (the License);
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an AS IS BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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

	// Ensure each project has a 'category' key
	foreach ($projects as &$project) {
   		 if (!isset($project['category'])) {
        		$project['category'] = 'other';
    		}
	}

        return $this->render('apps/index.html.twig', [
            'projects' => $projects,
        ]);
    }
}
