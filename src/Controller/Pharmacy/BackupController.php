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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Pharmacy\Stock;
use App\Entity\Pharmacy\Vente;
use App\Entity\Pharmacy\Achat;
use App\Entity\Pharmacy\Fournisseur;
//use App\Entity\Pharmacy\Customer;
use App\Entity\Pharmacy\Category;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[Route('/pharmacy/backup')]
#[IsGranted('ROLE_ADMIN')]
class BackupController extends AbstractController
{

    #[Route('/', name: 'app_pharmacy_backup')]
    public function backup(): Response
    {
        // Database credentials (use environment variables in production)
        $dbHost = '127.0.0.1';
        $dbUser = 'hamda';
        $dbPass = '211162hamda';
        $dbName = 'devengineerings_clean';

        $backupFile = tempnam(sys_get_temp_dir(), 'db_backup_').'.sql';
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            $this->addFlash('error', 'La sauvegarde a échoué.');
            return $this->redirectToRoute('app_pharmacy_index');
        }
        return $this->file($backupFile, 'backup_'.date('Ymd_His').'.sql', ResponseHeaderBag::DISPOSITION_ATTACHMENT);

       /* return $this->render('pharmacy/backup/index.html.twig', [
            'controller_name' => 'Pharmacy/BackupController',
        ]); */
    }


    #[Route('/excel', name: 'app_pharmacy_backup_excel')]
    public function backupExcel(EntityManagerInterface $em): Response
    {
        $spreadsheet = new Spreadsheet();

        // --- Sheet 1: Products (Stock) ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Produits');
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Désignation');
        $sheet->setCellValue('C1', 'Référence');
        $sheet->setCellValue('D1', 'Prix achat HT');
        $sheet->setCellValue('E1', 'Prix de Gros HT');
        $sheet->setCellValue('F1', 'Prix vente HT');
        $sheet->setCellValue('G1', 'TVA (%)');
        $sheet->setCellValue('H1', 'Prix vente TTC');
        $sheet->setCellValue('I1', 'Quantité');
        $sheet->setCellValue('J1', 'Stock min');
        $sheet->setCellValue('K1', 'Stock max');
        $sheet->setCellValue('L1', 'Description');
        $sheet->setCellValue('M1', 'Fournisseur');
        $sheet->setCellValue('N1', 'Catégorie');

        $products = $em->getRepository(Stock::class)->findAll();
        $row = 2;
        foreach ($products as $p) {
            $sheet->setCellValue('A'.$row, $p->getId());
            $sheet->setCellValue('B'.$row, $p->getDesignation());
            $sheet->setCellValue('C'.$row, $p->getReference());
            $sheet->setCellValue('D'.$row, $p->getPrixAchat());
            $sheet->setCellValue('E'.$row, $p->getPrixGros());
            $sheet->setCellValue('F'.$row, $p->getPrixDetail());
            $sheet->setCellValue('G'.$row, $p->getTvaPourcentage());
            $sheet->setCellValue('H'.$row, $p->getPrixDetail() * (1 + ($p->getTvaPourcentage() ?? 0)/100));
            $sheet->setCellValue('I'.$row, $p->getQuantite());
            $sheet->setCellValue('J'.$row, $p->getQuantiteMin());
            $sheet->setCellValue('K'.$row, $p->getQuantiteMax());
            $sheet->setCellValue('L'.$row, $p->getDescription());
            $sheet->setCellValue('M'.$row, $p->getFournisseur() ? $p->getFournisseur()->getRaisonSocial() : '');
            $sheet->setCellValue('N'.$row, $p->getCategorie() ? $p->getCategorie()->getNom() : '');
            $row++;
        }
        foreach (range('A','N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // --- Sheet 2: Sales ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ventes');
        $sheet2->setCellValue('A1', 'ID');
        $sheet2->setCellValue('B1', 'Référence');
        $sheet2->setCellValue('C1', 'Date');
        $sheet2->setCellValue('D1', 'Total HT');
        $sheet2->setCellValue('E1', 'TVA');
        $sheet2->setCellValue('F1', 'Total TTC');
        $sheet2->setCellValue('G1', 'Etat');
        $sales = $em->getRepository(Vente::class)->findAll();
        $row = 2;
        foreach ($sales as $v) {
            $sheet2->setCellValue('A'.$row, $v->getId());
            $sheet2->setCellValue('B'.$row, $v->getReference());
            $sheet2->setCellValue('C'.$row, $v->getDateVente()->format('d/m/Y H:i'));
            $sheet2->setCellValue('D'.$row, $v->getTotalHt());
            $sheet2->setCellValue('E'.$row, $v->getTotalTva());
            $sheet2->setCellValue('F'.$row, $v->getTotalTtc());
            $sheet2->setCellValue('G'.$row, $v->getEtat());
            $row++;
        }
        foreach (range('A','G') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // --- Sheet 3: Purchases ---
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Achats');
        $sheet3->setCellValue('A1', 'ID');
        $sheet3->setCellValue('B1', 'Référence');
        $sheet3->setCellValue('C1', 'Date');
        $sheet3->setCellValue('D1', 'Total HT');
        $sheet3->setCellValue('E1', 'TVA');
        $sheet3->setCellValue('F1', 'Total TTC');
        $sheet3->setCellValue('G1', 'Etat');
        $achats = $em->getRepository(Achat::class)->findAll();
        $row = 2;
        foreach ($achats as $a) {
            $sheet3->setCellValue('A'.$row, $a->getId());
            $sheet3->setCellValue('B'.$row, $a->getReference());
            $sheet3->setCellValue('C'.$row, $a->getDateAchat()->format('d/m/Y H:i'));
            $sheet3->setCellValue('D'.$row, $a->getTotalHt());
            $sheet3->setCellValue('E'.$row, $a->getTotalTva());
            $sheet3->setCellValue('F'.$row, $a->getTotalTtc());
            $sheet3->setCellValue('G'.$row, $a->getEtat());
            $row++;
        }
        foreach (range('A','G') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // --- Sheet 4: Suppliers (optional) ---
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Fournisseurs');
        $sheet4->setCellValue('A1', 'ID');
        $sheet4->setCellValue('B1', 'Raison sociale');
        $sheet4->setCellValue('C1', 'Responsable');
        $sheet4->setCellValue('D1', 'Adresse');
        $sheet4->setCellValue('E1', 'Téléphone Fixe');
        $sheet4->setCellValue('F1', 'Fax');
        $sheet4->setCellValue('G1', 'GSM');
        $sheet4->setCellValue('H1', 'Email');
        $fournisseurs = $em->getRepository(Fournisseur::class)->findAll();
        $row = 2;
        foreach ($fournisseurs as $f) {
            $sheet4->setCellValue('A'.$row, $f->getId());
            $sheet4->setCellValue('B'.$row, $f->getRaisonSocial());
            $sheet4->setCellValue('C'.$row, $f->getResponsable());
            $sheet4->setCellValue('D'.$row, $f->getAdresse());
            $sheet4->setCellValue('E'.$row, $f->getTelephoneFixe());
            $sheet4->setCellValue('F'.$row, $f->getFax());
            $sheet4->setCellValue('G'.$row, $f->getGsm());
            $sheet4->setCellValue('H'.$row, $f->getEmail());
            $row++;
        }
        foreach (range('A','H') as $col) {
            $sheet4->getColumnDimension($col)->setAutoSize(true);
        }
/*
        // --- Sheet 5: Customers (if any) ---
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Clients');
        $sheet5->setCellValue('A1', 'ID');
        $sheet5->setCellValue('B1', 'Raison sociale');
        $sheet5->setCellValue('C1', 'Adresse');
        $sheet5->setCellValue('D1', 'Téléphone');
        $sheet5->setCellValue('E1', 'Email');
        $customers = $em->getRepository(Customer::class)->findAll();
        $row = 2;
        foreach ($customers as $c) {
            $sheet5->setCellValue('A'.$row, $c->getId());
            $sheet5->setCellValue('B'.$row, $c->getRaisonSocial());
            $sheet5->setCellValue('C'.$row, $c->getAdresse());
            $sheet5->setCellValue('D'.$row, $c->getTelephone());
            $sheet5->setCellValue('E'.$row, $c->getEmail());
            $row++;
        }
        foreach (range('A','E') as $col) {
            $sheet5->getColumnDimension($col)->setAutoSize(true);
        }
*/
        // --- Sheet 5: Categories  ---
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Categories');
        $sheet5->setCellValue('A1', 'ID');
        $sheet5->setCellValue('B1', 'Nom');
        $customers = $em->getRepository(Category::class)->findAll();
        $row = 2;
        foreach ($customers as $c) {
            $sheet5->setCellValue('A'.$row, $c->getId());
            $sheet5->setCellValue('B'.$row, $c->getNom());
            $row++;
        }
        foreach (range('A','B') as $col) {
            $sheet5->getColumnDimension($col)->setAutoSize(true);
        }

        // Write the file and return it
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'backup_excel_') . '.xlsx';
        $writer->save($tempFile);

        return $this->file($tempFile, 'backup_' . date('Ymd_His') . '.xlsx', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

}
