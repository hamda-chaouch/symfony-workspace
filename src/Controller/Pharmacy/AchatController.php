<?php

namespace App\Controller\Pharmacy;

//use App\Entity\Pharmacy\AchatItem;
use App\Entity\Pharmacy\Achat;
use App\Form\Pharmacy\AchatType;
use App\Repository\Pharmacy\AchatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pharmacy\Stock;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Pharmacy\AchatItem;
use Symfony\Component\Form\FormError;

#[IsGranted('ROLE_ADMIN')]
#[Route('/pharmacy/achat')]
final class AchatController extends AbstractController
{
    #[Route(name: 'app_pharmacy_achat_index', methods: ['GET'])]
    public function index(AchatRepository $achatRepository): Response
    {
        return $this->render('pharmacy/achat/index.html.twig', [
            'achats' => $achatRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pharmacy_achat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $achat = new Achat();
        $achat->addAchatItem(new AchatItem()); // add one empty item

        //->This ensures the template will always have at least one row to clone.
        $form = $this->createForm(AchatType::class, $achat);

        // Debug: dump the form children
        //dump($form->createView()->children);
        //die;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$this->calculateTotals($achat);
            //commented because we used ORM/PrePersist/PreUpdate

            //error when try to pass a purshase without items when statuses in that list: 
            $forbiddenStatuses = ['confirmed', 'received', 'partially_received'];
            if ($achat->getAchatItems()->count() === 0 && in_array($achat->getEtat(), $forbiddenStatuses)) {
                //dump('test');
                //die;
                $this->addFlash('error', 'Vous ne pouvez pas enregistrer un achat avec ce statut sans aucun article: Changez letat à Draft ou Cancelled ou bien ajoutez des articles');
                // redirect
                return $this->redirectToRoute('app_pharmacy_achat_new');
                
                /* when validation fails we should not re-render -> Turbo error
                Turbo expects a redirect after form submission *//*
                return $this->render('pharmacy/achat/new.html.twig', [
                    'achat' => $achat,
                    'form' => $form->createView(),
                ]); */
                
            } 

            $exceeds = false;
            //update stock
            if ($achat->getEtat() === 'received') {
            
                foreach ($achat->getAchatItems() as $item) {
                    $product = $item->getProduit();
                    $newQty = $product->getQuantite() + $item->getQuantite();
                    $maxQty = $product->getQuantiteMax();

                    //check if stock exceed max allowed
                    if ($maxQty !== null && $newQty > $maxQty) {
/*                        $this->addFlash('error', sprintf(
                            'Article "%s" dépasserait max stock (%d > %d). Achat non enregistré! Stock non mis à jour.',
                            $product->getDesignation(), $newQty, $maxQty
                        )); 
*/
                        // Add error to the form instead of flash
                        $form->addError(new FormError(sprintf(
                            'Article "%s" dépasserait max stock (%d > %d). Achat non enregistre!',
                            $product->getDesignation(), $newQty, $maxQty
                        )));
                        $exceeds = true;
                        //dd($form->getErrors()); // dump errors and stop execution
                        //$achat->setEtat('Draft');
                        break; //stop on first error
                    }else{
                    $product->setQuantite($newQty);
                    }
                }
            }


            //if quantity exceed max stock we stay on new formula page
            if ($exceeds) {
                // Do NOT persist; redirect back to form with errors
                return $this->render('pharmacy/achat/new.html.twig', [
                    'achat' => $achat,
                    'form' => $form, //<-- createView added
                ]);
                //return $this->redirectToRoute('app_pharmacy_achat_new');

            }

            $entityManager->persist($achat);
            $entityManager->flush();
            //$this->addFlash('success', 'Achat enregistré avec succès.');
            return $this->redirectToRoute('app_pharmacy_achat_index');
        }

        return $this->render('pharmacy/achat/new.html.twig', [
            'achat' => $achat,
            'form' => $form, //<-- createView added
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_achat_show', methods: ['GET'])]
    public function show(Achat $achat): Response
    {
        //$this->addFlash('warning', 'Test flash message');
        return $this->render('pharmacy/achat/show.html.twig', [
            'achat' => $achat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pharmacy_achat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Achat $achat, EntityManagerInterface $entityManager): Response
    {
        //Cannot edit a cancelled purchase
        if ($achat->getEtat() === 'cancelled') {
            $this->addFlash('error', 'Cannot edit a cancelled purchase.');
            return $this->redirectToRoute('app_pharmacy_achat_show',
                ['id' => $achat->getId()]);
        }

        // Store original item quantities before form handling
        $originalQuantities = [];
        foreach ($achat->getAchatItems() as $item) {
            $originalQuantities[$item->getId()] = $item->getQuantite();
        }

        //store old status to be compared to new one:
        // Case 1: Status remains 'received' → adjust stock by delta
        //Case 3: if old=recived && new=!recived -> substract from stock
        //Case 2: if old=!recieved && new=recieved -> add to stock
        $oldStatus = $achat->getEtat();

        $form = $this->createForm(AchatType::class, $achat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Additional check: empty item list and new status is 'Received'       
            $forbiddenStatuses = ['confirmed', 'received', 'partially_received'];
            if ($achat->getAchatItems()->count() === 0 && in_array($achat->getEtat(), $forbiddenStatuses)) {
                $this->addFlash('error', 'Un achat avec ce statut doit contenir au moins un article: Changez letat à Draft ou Cancelled ou bien ajoutez des articles');
                      
                /* when validation fails we should not re-render -> Turbo error
                Turbo expects a redirect after form submission *//*
                return $this->render('pharmacy/achat/edit.html.twig', [
                    'achat' => $achat,
                    'form' => $form->createView(),
                ]); */
                return $this->redirectToRoute('app_pharmacy_achat_edit', 
                    ['id' => $achat->getId()]);
            }

            $exceeds = false;

            //check status and update stock
            $newStatus = $achat->getEtat();

            //if ($oldStatus !== $newStatus) {
                // Case 1: Status remains 'received' → adjust stock by delta       
                if ($oldStatus === 'received' &&$newStatus === 'received') {
                    foreach ($achat->getAchatItems() as $item) {
                        $product = $item->getProduit();
                        $oldQty = $originalQuantities[$item->getId()] ?? 0;
                        $newQty = $item->getQuantite();
                        $delta = $newQty - $oldQty;
                        if ($delta == 0) continue;
                        $newStock = $product->getQuantite() + $delta;

                        $maxQty = $product->getQuantiteMax();
                        $minQty = $product->getQuantiteMin();
                        //check if stock exceed max allowed
                        if ($maxQty !== null && $newStock>$maxQty && $delta>0) {
                            $this->addFlash('error', sprintf(
                                'Article "%s" dépasserait Max Stock (%d > %d). Achat non enregistré. Stock non mis à jour.',
                                $product->getDesignation(), $newStock, $maxQty
                            ));
                            $exceeds = true;
                            break;

                        }elseif ($minQty !== null && $newStock<$minQty && $delta<0) {
                            $this->addFlash('error', sprintf(
                                'Article "%s" baisserait sous Min Stock (%d > %d). Achat non enregistré. Stock non mis à jour.',
                                $product->getDesignation(), $newStock, $maxQty
                            ));
                            $exceeds = true;
                            break;

                        }else{
                            $product->setQuantite($newStock);
                        }
                    }
                // Case 2: Status changed to 'received' (from draft/confirmed/…)
                // → add full new quantities
                }elseif ($oldStatus !== 'received' && $newStatus === 'received') {
                    foreach ($achat->getAchatItems() as $item) {
                        $product = $item->getProduit();
                        $newQty = $item->getQuantite();
                        $newStock = $product->getQuantite() + $newQty;
                        $maxQty = $product->getQuantiteMax();
                        if ($maxQty !== null && $newStock > $maxQty) {
                            $this->addFlash('error', sprintf(
                                'Article "%s" dépasserait le stock maximum (%d > %d). Opération annulée!',
                                $product->getDesignation(), $newStock, $maxQty
                             ));
                             $exceeds = true;
                             break;
                        }
                        $product->setQuantite($newStock);
                    }
                
                // Case 3: Status changed away from 'received' 
                //(to draft/confirmed/…) → subtract full old quantities
                } elseif ($oldStatus === 'received' && $newStatus !== 'received') {
                    foreach ($achat->getAchatItems() as $item) {
                        $product = $item->getProduit();
                        $oldQty = $originalQuantities[$item->getId()] ?? 0;    
                        //$newQty = $item->getQuantite();
                        //$delta = $newQty - $oldQty;
                        $newStock = $product->getQuantite() - $oldQty;

                        //$maxQty = $product->getQuantiteMax();
                        $minQty = $product->getQuantiteMin();

                        if ($minQty !== null && $newStock<$minQty){
                            $this->addFlash('error', sprintf(
                                'Article "%s" baisserait sous Stock minimum (%d < %d). Operation annulée!',
                                $product->getDesignation(), $newStock, $minQty
                            ));
                            $exceeds = true; 
                            break;
                        }else{
                            $product->setQuantite($newStock);
                        }
                        
                    }
                }
            //}


            if ($exceeds) {
                    // Rétablir lancien statut pour lachat
                    $achat->setEtat($oldStatus);
                    // Discard all changes made to the entity (including items)
                    $entityManager->refresh($achat);
                    return $this->redirectToRoute('app_pharmacy_achat_edit', ['id' => $achat->getId()]);
            }

            //collection changes don't trigger ORM\PreUpdate (only fires when
            // the entity's own scalar fields change)↓
            $achat->calculateTotals();
            $entityManager->flush();
            return $this->redirectToRoute('app_pharmacy_achat_index');
        }

        return $this->render('pharmacy/achat/edit.html.twig', [
            'achat' => $achat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pharmacy_achat_delete', methods: ['POST'])]
    public function delete(Request $request, Achat $achat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$achat->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($achat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pharmacy_achat_index', [], Response::HTTP_SEE_OTHER);
    }
/*
    private function calculateTotals(Achat $achat): void
    {
        $totalHt = 0;
        foreach ($achat->getAchatItems() as $item) {
            $totalHt += $item->getQuantite() * $item->getPrixUnitaireHt();
        }
        $this->total_ht = $totalHt;
        $tvaRate = $this->tva_pourcentage ?? 0;
        $this->total_tva = $totalHt * ($tvaRate / 100);
        $this->total_ttc = $totalHt + $this->total_tva);
    }
*/

    #[Route('/{id}/cancel', name: 'app_pharmacy_achat_cancel', methods: ['POST'])]
    public function cancel(Request $request, Achat $achat, EntityManagerInterface $entityManager): Response
    {
        // CSRF protection
        if (!$this->isCsrfTokenValid('cancel' . $achat->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_pharmacy_achat_index');
        }

        // Already cancelled? Nothing to do.
        if ($achat->getEtat() === 'cancelled') {
            $this->addFlash('warning', 'Purchase is already cancelled.');
            return $this->redirectToRoute('app_pharmacy_achat_index');
        }

        $exceeds = false;
        
        // If status was 'received', reverse stock
        if ($achat->getEtat() === 'received') {
            foreach ($achat->getAchatItems() as $item) {
                $product = $item->getProduit();
                $newStock = $product->getQuantite() - $item->getQuantite();
                // check for negative stock
                $minQty = $product->getQuantiteMin();
                if ($minQty !== null && $newStock<$minQty){
                    $this->addFlash('error', sprintf(
                        'Article "%s" baisserait sous le Stock minimum (%d < %d). Annulation impossible.',
                        $product->getDesignation(), $newStock, $minQty
                    ));
                    $exceeds = true; 
                    break; //stop processing further items
                }
                $product->setQuantite($newStock);
            }
        }

        if($exceeds){
            $entityManager->refresh($achat);
            //return $this->redirectToRoute('app_pharmacy_achat_edit',['id' => $achat->getId()]);
        }else{
            // Set status to cancelled
            $achat->setEtat('cancelled');
            $entityManager->flush();
            $this->addFlash('success', 'Purchase cancelled successfully.');
        }
        return $this->redirectToRoute('app_pharmacy_achat_index');

    }

    #[Route('/get-products/{supplierId}', name: 'get_products_by_supplier', methods: ['GET'])]
    public function getProductsBySupplier(int $supplierId, EntityManagerInterface $entityManager): JsonResponse
    {
        $products = $entityManager->getRepository(Stock::class)
            ->createQueryBuilder('s')
            ->where('s.fournisseur = :fournisseur')
            ->setParameter('fournisseur', $supplierId)
            ->orderBy('s.designation', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'designation' => $product->getDesignation(),
                'prix_unitaire_ht' => $product->getPrixAchat(),
                'tva_pourcentage'  => $product->getTvaPourcentage(),
            ];
        }

        return $this->json($data);
    }
}
