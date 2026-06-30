<?php

namespace App\Controller\Pharmacy;

use App\Entity\Pharmacy\Stock;
use App\Form\Pharmacy\StockType;
use App\Repository\Pharmacy\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pharmacy\AchatItem;
use App\Entity\Pharmacy\VenteItem;

#[IsGranted('ROLE_ADMIN')]
#[Route('/pharmacy/stock')]
final class StockController extends AbstractController
{
    #[Route(name: 'app_pharmacy_stock_index', methods: ['GET'])]
    public function index(StockRepository $stockRepository): Response
    {
        return $this->render('pharmacy/stock/index.html.twig', [
            'stocks' => $stockRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pharmacy_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stock);
            $entityManager->flush();

            return $this->redirectToRoute('app_pharmacy_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/stock/new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        return $this->render('pharmacy/stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pharmacy_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pharmacy_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stock->getId(), $request->getPayload()->getString('_token'))) {
            // Check if product is used in any purchase or sale item
            $achatItemCount = $entityManager->getRepository(AchatItem::class)->count(['produit' => $stock]);
            $venteItemCount = $entityManager->getRepository(VenteItem::class)->count(['produit' => $stock]);
            if ($achatItemCount > 0 || $venteItemCount > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce Produit car il est référencé dans des Achats ou des Ventes.');
                return $this->redirectToRoute('app_pharmacy_stock_index');
            }
            $entityManager->remove($stock);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé.');

        }

        return $this->redirectToRoute('app_pharmacy_stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
