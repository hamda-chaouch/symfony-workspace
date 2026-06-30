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

use App\Entity\Pharmacy\Vente;
use App\Form\Pharmacy\VenteType;
use App\Repository\Pharmacy\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pharmacy\Stock;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Pharmacy\VenteItem;
use App\Entity\Pharmacy\Invoice;
use Symfony\Component\Form\FormError;

#[Route('/pharmacy/vente')]
final class VenteController extends AbstractController
{
    #[Route(name: 'app_pharmacy_vente_index', methods: ['GET'])]
    public function index(VenteRepository $venteRepository): Response
    {
        return $this->render('pharmacy/vente/index.html.twig', [
            'ventes' => $venteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pharmacy_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vente = new Vente();

        //set the current  date automatically 
        $vente->setDateVente(new \DateTimeImmutable()); // current timestamp

        // Add one empty item to ensure the form has at least one row
        $vente->addVenteItem(new VenteItem());

        $form = $this->createForm(VenteType::class, $vente, [
             'is_edit' => false,
        ]);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            //$this->calculateTotals($achat);
            //commented because we used ORM/PrePersist/PreUpdate

            // Check if sale has items when status is 'delivered' (or any status that affects stock)
           $isStockAffectingStatus = in_array($vente->getEtat(), ['delivered', 'confirmed']);
           if ($vente->getVenteItems()->count() === 0 && in_array($vente->getEtat(), $isStockAffectingStatus)) {
                $this->addFlash('error', 'Vous ne pouvez pas enregistrer une vente sans article lorsque le statut est "livrée" ou "confirmée".');
                return $this->redirectToRoute('app_pharmacy_vente_new');

                /* when validation fails we should not re-render -> Turbo error
                Turbo expects a redirect after form submission *//*
                return $this->render('pharmacy/vente/new.html.twig', [
                    'vente' => $vente,
                    'form' => $form->createView(),
                ]); */
            }


            $exceeds = false;
            if ($vente->getEtat() === 'delivered') {
                foreach ($vente->getVenteItems() as $item) {
                    $product = $item->getProduit();
                    $newStock = $product->getQuantite() - $item->getQuantite();
                    $minQty = $product->getQuantiteMin();

                    // Block if stock would go below minimum (or below zero if no min defined)
                    if($minQty !== null && $newStock < $minQty/* || $newStock < 0*/) {
/*                        $this->addFlash('error', sprintf(
                            'Stock insuffisant pour "%s" (disponible: %d, demandé: %d)',
                            $product->getDesignation(), $product->getQuantite(), $item->getQuantite()
                        ));
*/
                        $form->addError(new FormError(sprintf(
                            'Stock insuffisant pour "%s" (disponible: %d, demandé: %d)',
                             $product->getDesignation(), $product->getQuantite(),
                             $item->getQuantite()
                        )));
                        $exceeds = true;
                        break;
                    }
                    $product->setQuantite($newStock);
                }
            }

            if ($exceeds) {
                return $this->render('pharmacy/vente/new.html.twig', [
                    'vente' => $vente,
                    'form' => $form->createView(),
                ]);
            }


            // Force totals recalculation
            foreach ($vente->getVenteItems() as $item) {
                $item->calculateLineTotal();
            }
            $vente->calculateTotals();

            //Invoice calculation:
            // After stock update (if status delivered and no invoice yet)
            if ($vente->getEtat() === 'delivered' && $vente->getInvoices()->isEmpty()) {
                $invoice = new Invoice();
                $invoice->setVente($vente);
                //$invoice->setInvoiceNumber($this->generateInvoiceNumber());
                $invoice->setInvoiceNumber($vente->getReference());
                $invoice->setDate(new \DateTimeImmutable());   
                $invoice->setTotalHt($vente->getTotalHt() ?? 0.0);
                $invoice->setTotalTva($vente->getTotalTva() ?? 0.0);
                $invoice->setTotalTtc($vente->getTotalTtc() ?? 0.0);
                $invoice->setStatus('unpaid');
                $entityManager->persist($invoice);
            }


            // then persist and flush
            $entityManager->persist($vente);
            $entityManager->flush();
            //$this->addFlash('success', 'Vente créée avec succès.');
            return $this->redirectToRoute('app_pharmacy_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_vente_show', methods: ['GET'])]
    public function show(Vente $vente): Response
    {
        return $this->render('pharmacy/vente/show.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pharmacy_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {

        //Cannot edit a cancelled purchase
        if ($vente->getEtat() === 'cancelled') {
            $this->addFlash('error', 'Vous pouvez pas modifier une Vente Annulee.');
            return $this->redirectToRoute('app_pharmacy_vente_show',
                ['id' => $vente->getId()]);
        }

        // Store original item quantities and old status before form binding and handling
        $originalQuantities = [];
        foreach ($vente->getVenteItems() as $item) {
            $originalQuantities[$item->getId()] = $item->getQuantite();
        }

        //store old status to be compared with new one
        // Case 1: status unchanged and is 'delivered' → adjust stock by delta
        // Case 2: status changed to 'delivered' → subtract full new quantities
        // Case 3: status changed away from 'delivered' → add back original quantities (no min check needed)
        $oldStatus = $vente->getEtat();

        $form = $this->createForm(VenteType::class, $vente, [
              'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $forbiddenStatuses = ['confirmed', 'delivered'];
            if ($vente->getVenteItems()->count() === 0 && in_array($vente->getEtat(), $forbiddenStatuses)) {
                $this->addFlash('error', 'Une Vente avec ce statut doit contenir au moins un article: Changez letat à Draft ou Cancelled ou bien ajoutez des articles');

                return $this->redirectToRoute('app_pharmacy_vente_edit', 
                    ['id' => $vente->getId()]);
            }

            //extract status
            $newStatus = $vente->getEtat();
            //initialisation 
            $exceeds = false;

      
            // Case 1: status unchanged and is 'delivered' → adjust stock by delta
            if ($oldStatus === 'delivered' && $newStatus === 'delivered') {
                foreach ($vente->getVenteItems() as $item) {
                    $product = $item->getProduit();
                    $oldQty = $originalQuantities[$item->getId()] ?? 0;
                    $newQty = $item->getQuantite();
                    $delta = $newQty - $oldQty;
                    if ($delta == 0) continue;

                    $newStock = $product->getQuantite() - $delta;

                    $maxQty = $product->getQuantiteMax();
                    $minQty = $product->getQuantiteMin();
                    //check if stock exceed min allowed
                    if ($minQty !== null && $newStock<$minQty && $delta>0) {
                        $this->addFlash('error', sprintf(
                        'Modification impossible pour "%s": stock résultant (%d) sous le minimum (%d)',
                        $product->getDesignation(), $newStock, $product->getQuantiteMin() ?? 0
                        ));
                        $exceeds = true;
                        break;

                    }elseif ($maxQty !== null && $newStock>$maxQty && $delta<0) {
                        $this->addFlash('error', sprintf(
                            'Article "%s" depasserait au-dessus du Max Stock (%d < %d). Vente non enregistré. Stock non mis à jour.',
                            $product->getDesignation(), $newStock, $maxQty
                        ));
                        $exceeds = true;
                        break;

                    }else{
                        $product->setQuantite($newStock);
                    }
                }


            // Case 2: status changed to 'delivered' → subtract full new quantities
            }elseif ($oldStatus !== 'delivered' && $newStatus === 'delivered') {
                foreach ($vente->getVenteItems() as $item) {
                    $product = $item->getProduit();
                    $newQty = $item->getQuantite();
                    $newStock = $product->getQuantite() - $newQty;
                    $maxQty = $product->getQuantiteMax();
                    if ($minQty !== null && $newStock < $minQty) {
                        $this->addFlash('error', sprintf(
                        'Stock insuffisant pour "%s". Actuel: %d, demandé: %d, max autorisée: %d',
                        $product->getDesignation(), $product->getQuantite(), $newQty, 
                        $product->getQuantite() - $product->getQuantiteMin()
                        ));
                        $exceeds = true;
                        break;
                    }
                    $product->setQuantite($newStock);
                }
            }

            // Case 3: status changed away from 'delivered' → add back original quantities (no min check needed)
            elseif ($oldStatus === 'delivered' && $newStatus !== 'delivered') {
                foreach ($vente->getVenteItems() as $item) {
                    $product = $item->getProduit();
                    $oldQty = $originalQuantities[$item->getId()] ?? 0;
                    $product->setQuantite($product->getQuantite() + $oldQty);
                    $maxQty = $product->getQuantiteMax();
                    $newStock = $product->getQuantite() + $oldQty;

                    if ($maxQty !== null && $newStock>$maxQty){
                        $this->addFlash('error', sprintf(
                            'Article "%s" depasserait au-dessus du Stock maximum (%d > %d). Operation annulée!',
                            $product->getDesignation(), $newStock, $maxQty
                        ));
                        $exceeds = true;
                        break; 
                    }else{
                        $product->setQuantite($newStock);   
                    }
                }
            }


            if ($exceeds) {
                // Revert status and discard changes (refresh the entity)
                $vente->setEtat($oldStatus);
                // Discard all changes made to the entity (including items)
                $entityManager->refresh($vente);
                //$this->addFlash('error', 'Modification annulée : stock minimum non respecté ou liste d’articles vide.');
                return $this->redirectToRoute('app_pharmacy_vente_edit', ['id' => $vente->getId()]);
            }



            // Recalculate line totals (in case items changed)
            foreach ($vente->getVenteItems() as $item) {
                $item->calculateLineTotal();
            }
            $vente->calculateTotals();
            // Ensure totals recalculated (the entity's lifecycle callbacks will also run)
            $vente->calculateTotals();



            // Generate invoice when status changes to 'delivered' and no invoice exists yet
            if ($newStatus === 'delivered' && $vente->getInvoices()->isEmpty()) {
  	        $invoice = new Invoice();
 	        $invoice->setVente($vente);
  	        //$invoice->setInvoiceNumber($this->generateInvoiceNumber());
  	        $invoice->setInvoiceNumber($vente->getReference());
                $invoice->setDate(new \DateTimeImmutable());
  	        $invoice->setTotalHt($vente->getTotalHt() ?? 0.0);
                $invoice->setTotalTva($vente->getTotalTva() ?? 0.0);
  	        $invoice->setTotalTtc($vente->getTotalTtc() ?? 0.0);
 	        $invoice->setStatus('unpaid');
   	        $entityManager->persist($invoice);
       	        $this->addFlash('success', 'Facture générée automatiquement.');
    	    }

            // Generate credit note when status changes to 'cancelled' and there is an invoice
            if ($newStatus === 'cancelled' && $oldStatus === 'delivered') {
                //If the collection has at least one element, it returns the first Invoice 
                //object (the one with the lowest ID, typically the earliest created).
                $invoice = $vente->getInvoices()->first();
                //-> If the collection is empty, it returns null
                if ($invoice && $invoice->getCreditNotes()->isEmpty()) {
                    $creditNote = new CreditNote();
                    $creditNote->setInvoice($invoice);
                    $creditNote->setCreditNumber('CN-' . date('Ymd') . '-' . uniqid());
                    $creditNote->setDate(new \DateTimeImmutable());
                    $creditNote->setAmount($invoice->getTotalTtc() ?? 0.0);
                    $creditNote->setReason('Annulation de la vente');
                    $entityManager->persist($creditNote);
                    $this->addFlash('info', 'Avoir généré suite à l’annulation.');
                }
            }



            $entityManager->flush();
            return $this->redirectToRoute('app_pharmacy_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pharmacy/vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_vente_delete', methods: ['POST'])]
    public function delete(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vente->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pharmacy_vente_index', [], Response::HTTP_SEE_OTHER);
    }



    #[Route('/{id}/cancel', name: 'app_pharmacy_vente_cancel', methods: ['POST'])]
    public function cancel(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        // CSRF protection
        if (!$this->isCsrfTokenValid('cancel' . $vente->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_pharmacy_vente_index');
        }

        // Already cancelled
        if ($vente->getEtat() === 'cancelled') {
            $this->addFlash('warning', 'Cette vente est déjà annulée.');
            return $this->redirectToRoute('app_pharmacy_vente_index');
        }

        $exceeds = false;
        // If status was 'delivered', we need to restore stock
        if ($vente->getEtat() === 'delivered') {
            foreach ($vente->getVenteItems() as $item) {
                $product = $item->getProduit();
                $newStock = $product->getQuantite() + $item->getQuantite();
                $maxQty = $product->getQuantiteMax();
                if ($maxQty !== null && $newStock > $maxQty) {
                    $this->addFlash('error', sprintf(
                        'Impossible d’annuler : le produit "%s" dépasserait le stock maximum (%d > %d).',
                        $product->getDesignation(), $newStock, $maxQty
                    ));
                    $exceeds = true;
                    break; //stop processing further items
                }
                    // No exceed: restore stock
                    $product->setQuantite($product->getQuantite() + $item->getQuantite()); 
            }
        }

        if ($exceeds) {
            $entityManager->refresh($achat);
            //return $this->redirectToRoute('app_pharmacy_vente_show', ['id' => $vente->getId()]);
            //return $this->redirectToRoute('app_pharmacy_vente_edit',['id' => $vente->getId()]);
        }else{
            // Set status to cancelled
            $vente->setEtat('cancelled');
            $entityManager->flush();
            $this->addFlash('success', 'Vente Annulee avec succee.Le stock a été rétabli.');
        }
        return $this->redirectToRoute('app_pharmacy_vente_index');
    }
/*
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . uniqid();
    }
*/
}
