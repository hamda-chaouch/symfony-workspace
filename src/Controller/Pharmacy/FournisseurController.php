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


<?php

namespace App\Controller\Pharmacy;

use App\Entity\Pharmacy\Fournisseur;
use App\Form\Pharmacy\FournisseurType;
use App\Repository\Pharmacy\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_ADMIN')]
#[Route('/pharmacy/fournisseur')]
final class FournisseurController extends AbstractController
{
    #[Route(name: 'app_pharmacy_fournisseur_index', methods: ['GET'])]
    public function index(FournisseurRepository $fournisseurRepository): Response
    {
        return $this->render('pharmacy/fournisseur/index.html.twig', [
            'fournisseurs' => $fournisseurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pharmacy_fournisseur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $fournisseur = new Fournisseur();
        $form = $this->createForm(FournisseurType::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($fournisseur);
            $entityManager->flush();

            return $this->redirectToRoute('app_pharmacy_fournisseur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/fournisseur/new.html.twig', [
            'fournisseur' => $fournisseur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_fournisseur_show', methods: ['GET'])]
    public function show(Fournisseur $fournisseur): Response
    {
        return $this->render('pharmacy/fournisseur/show.html.twig', [
            'fournisseur' => $fournisseur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pharmacy_fournisseur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Fournisseur $fournisseur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FournisseurType::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pharmacy_fournisseur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/fournisseur/edit.html.twig', [
            'fournisseur' => $fournisseur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_fournisseur_delete', methods: ['POST'])]
    public function delete(Request $request, Fournisseur $fournisseur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fournisseur->getId(), $request->getPayload()->getString('_token'))) {
            // Check if supplier has any purshases
            if ($fournisseur->getAchats()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce Fournisseur car il a des Achats associées.');
                return $this->redirectToRoute('app_pharmacy_fournisseur_index');
            }
            $entityManager->remove($fournisseur);
            $entityManager->flush();
            $this->addFlash('success', 'Fournisseur supprimé.');
        }

        return $this->redirectToRoute('app_pharmacy_fournisseur_index', [], Response::HTTP_SEE_OTHER);
    }
}
