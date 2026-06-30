<?php

namespace App\Controller\Pharmacy;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pharmacy\CreditNote;
use App\Repository\Pharmacy\CreditNoteRepository;

#[Route('/pharmacy/credit/note')]
final class CreditNoteController extends AbstractController
{
    #[Route('/', name: 'app_pharmacy_credit_note_index')]
    public function index(CreditNoteRepository $repo): Response
    {

        $creditNotes = $repo->findAll();
        return $this->render('pharmacy/credit_note/index.html.twig', [
            'credit_notes' => $creditNotes,
        ]);
    }

    #[Route('/{id}', name: 'app_credit_note_show')]
    public function show(CreditNote $creditNote): Response
    {
        return $this->render('pharmacy/credit_note/show.html.twig', [
            'credit_note' => $creditNote,
        ]);
    }

}
