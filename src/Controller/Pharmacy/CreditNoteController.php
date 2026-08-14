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
