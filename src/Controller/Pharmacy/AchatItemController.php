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



namespace App\Controller\Pharmacy;

use App\Entity\Pharmacy\AchatItem;
use App\Form\Pharmacy\AchatItemType;
use App\Repository\Pharmacy\AchatItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pharmacy/achat/item')]
final class AchatItemController extends AbstractController
{
    #[Route(name: 'app_pharmacy_achat_item_index', methods: ['GET'])]
    public function index(AchatItemRepository $achatItemRepository): Response
    {
        return $this->render('pharmacy/achat_item/index.html.twig', [
            'achat_items' => $achatItemRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pharmacy_achat_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $achatItem = new AchatItem();
        $form = $this->createForm(AchatItemType::class, $achatItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($achatItem);
            $entityManager->flush();

            return $this->redirectToRoute('app_pharmacy_achat_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/achat_item/new.html.twig', [
            'achat_item' => $achatItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_achat_item_show', methods: ['GET'])]
    public function show(AchatItem $achatItem): Response
    {
        return $this->render('pharmacy/achat_item/show.html.twig', [
            'achat_item' => $achatItem,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pharmacy_achat_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AchatItem $achatItem, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AchatItemType::class, $achatItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pharmacy_achat_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/achat_item/edit.html.twig', [
            'achat_item' => $achatItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_achat_item_delete', methods: ['POST'])]
    public function delete(Request $request, AchatItem $achatItem, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$achatItem->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($achatItem);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pharmacy_achat_item_index', [], Response::HTTP_SEE_OTHER);
    }
}
