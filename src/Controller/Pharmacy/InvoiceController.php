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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pharmacy\Invoice;
use App\Repository\Pharmacy\InvoiceRepository;

#[Route('/pharmacy/invoice')]
final class InvoiceController extends AbstractController
{
    #[Route('/', name: 'app_pharmacy_invoice_index')]
    public function index(InvoiceRepository $repo): Response
    {
        $invoices = $repo->findAll();

        return $this->render('pharmacy/invoice/index.html.twig', [
            'invoices' => $invoices,
        ]);
    }

    #[Route('/{id}', name: 'app_invoice_show')]
    public function show(Invoice $invoice): Response
    {
        return $this->render('pharmacy/invoice/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }

}
