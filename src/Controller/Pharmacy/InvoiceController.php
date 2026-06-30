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
