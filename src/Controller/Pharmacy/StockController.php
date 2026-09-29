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
// ← NEW: needed to populate the Category dropdown
use App\Entity\Pharmacy\Category;
// ← NEW: needed to populate the Supplier dropdown
use App\Entity\Pharmacy\Fournisseur;

#[IsGranted('ROLE_ADMIN')]
#[Route('/pharmacy/stock')]
final class StockController extends AbstractController
{
    #[Route(name: 'app_pharmacy_stock_index', methods: ['GET'])]
    public function index(
        Request $request,                    // ← NEW: needed to read the filter query params
        StockRepository $stockRepository,
        EntityManagerInterface $em           // ← NEW: needed to fetch categories and suppliers
    ): Response {
        // ---------- 1. Read filter values from the URL query string ----------
        // Each filter is optional. Default '' means "no filter applied".
         $search     = $request->query->get('search', '');
         $categoryId = $request->query->get('category');
        $supplierId = $request->query->get('supplier');
        $status     = $request->query->get('status', '');
        $expiration = $request->query->get('expiration', '');

        // ← NEW: quantity range filter (used to find products with 0 stock, etc.)
        // NOTE: we read as-is (no default '') because "0" is a valid value
        $qtyMin     = $request->query->get('qty_min');
        $qtyMax     = $request->query->get('qty_max');

        // ---------- 2. Build the query with QueryBuilder ----------
        // We use QueryBuilder instead of findAll() so we can chain conditions.
        $qb = $stockRepository->createQueryBuilder('s')
            ->leftJoin('s.categorie', 'c')     // join category so we can filter by it
            ->leftJoin('s.fournisseur', 'f');  // join supplier so we can filter by it

        // --- Filter: text search on designation OR reference ---
        if (!empty($search)) {
            $qb->andWhere('s.designation LIKE :search OR s.reference LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // --- Filter: category ---
        if (!empty($categoryId)) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        // --- Filter: supplier ---
        if (!empty($supplierId)) {
            $qb->andWhere('f.id = :supplierId')
               ->setParameter('supplierId', $supplierId);
        }

        // --- Filter: stock status (ok / low / out / over) ---
        if ($status === 'out') {
            // Out of stock
            $qb->andWhere('s.quantite = 0');
        } elseif ($status === 'low') {
            // Low = has a min threshold defined, quantity <= min, but not zero
            $qb->andWhere('s.quantite_min IS NOT NULL')
               ->andWhere('s.quantite <= s.quantite_min')
               ->andWhere('s.quantite > 0');
        } elseif ($status === 'over') {
            // Overstocked = has a max threshold and quantity >= max
            $qb->andWhere('s.quantite_max IS NOT NULL')
               ->andWhere('s.quantite >= s.quantite_max');
        } elseif ($status === 'ok') {
            // OK = not zero, above min, below max
            $qb->andWhere('s.quantite > 0')
               ->andWhere('(s.quantite_min IS NULL OR s.quantite > s.quantite_min)')
               ->andWhere('(s.quantite_max IS NULL OR s.quantite < s.quantite_max)');
        }

        // --- Filter: expiration ---
        $today     = new \DateTimeImmutable('today');
        $threshold = $today->modify('+30 days'); // used for "expiring soon"
        if ($expiration === 'expired') {
            $qb->andWhere('s.date_expiration IS NOT NULL')
               ->andWhere('s.date_expiration < :today')
               ->setParameter('today', $today);
        } elseif ($expiration === 'soon') {
            $qb->andWhere('s.date_expiration IS NOT NULL')
               ->andWhere('s.date_expiration >= :today')
               ->andWhere('s.date_expiration <= :threshold')
               ->setParameter('today', $today)
               ->setParameter('threshold', $threshold);
        } elseif ($expiration === 'none') {
            $qb->andWhere('s.date_expiration IS NULL');
        }

        // --- ← NEW: Filter: quantity range ---
        // IMPORTANT: we check against null AND '' because "0" is a valid value.
        // Using !empty() would ignore qty_min=0, which is exactly the case
        // we want to catch (find out-of-stock products).
        if ($qtyMin !== null && $qtyMin !== '') {
            $qb->andWhere('s.quantite >= :qtyMin')
               ->setParameter('qtyMin', (int) $qtyMin);
        }
        if ($qtyMax !== null && $qtyMax !== '') {
            $qb->andWhere('s.quantite <= :qtyMax')
               ->setParameter('qtyMax', (int) $qtyMax);
         }

        // ---------- 3. Order and execute ----------
        $qb->orderBy('s.designation', 'ASC');
        $stocks = $qb->getQuery()->getResult();

        // ---------- 4. Data for the dropdowns ----------
        $categories   = $em->getRepository(Category::class)->findAll();
        $fournisseurs = $em->getRepository(Fournisseur::class)->findAll();

        // ---------- 5. Render ----------
        return $this->render('pharmacy/stock/index.html.twig', [
            'stocks'       => $stocks,
            'categories'   => $categories,
            'fournisseurs' => $fournisseurs,
            // We pass the current filter values back so the form keeps its state
            'filters'      => [
                'search'     => $search,
                'category'   => $categoryId,
                'supplier'   => $supplierId,
                'status'     => $status,
                'expiration' => $expiration,
                'qty_min'    => $qtyMin,   // ← NEW
                'qty_max'    => $qtyMax,   // ← NEW
            ],
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
