<?php

namespace App\Controller\Pharmacy;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pharmacy\Vente;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\Pharmacy\Achat;
use App\Repository\Pharmacy\StockRepository;

final class DashboardController extends AbstractController
{
    #[Route('/pharmacy', name: 'pharmacy_dashboard')]
    public function index(EntityManagerInterface $em, StockRepository $stockRepo): Response
    {
        // Get today's date (start and end)
        $todayStart = new \DateTime('today midnight');
        $todayEnd = new \DateTime('tomorrow midnight');

        // Get first day of current month
        $monthStart = new \DateTime('first day of this month midnight');
        $monthEnd = new \DateTime('first day of next month midnight');

        // Get first day of current year
        $yearStart = new \DateTime('first day of January midnight');
        $yearEnd = new \DateTime('first day of January next year midnight');

        $venteRepo = $em->getRepository(Vente::class);

        // Sum of daily sales
        $dailyTotal = $venteRepo->createQueryBuilder('v')
            ->select('SUM(v.total_ttc)')
            ->where('v.date_vente BETWEEN :start AND :end')
            ->andWhere('v.etat = :status')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->setParameter('status', 'delivered')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Monthly sales
        $monthlyTotal = $venteRepo->createQueryBuilder('v')
            ->select('SUM(v.total_ttc)')
            ->where('v.date_vente BETWEEN :start AND :end')
            ->andWhere('v.etat = :status')
            ->setParameter('start', $monthStart)
            ->setParameter('end', $monthEnd)
            ->setParameter('status', 'delivered')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Yearly sales
        $yearlyTotal = $venteRepo->createQueryBuilder('v')
            ->select('SUM(v.total_ttc)')
            ->where('v.date_vente BETWEEN :start AND :end')
            ->andWhere('v.etat = :status')
            ->setParameter('start', $yearStart)
            ->setParameter('end', $yearEnd)
            ->setParameter('status', 'delivered')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;


        // Sum of purchases received today (status 'received')
        $dailyPurchases = $em->getRepository(Achat::class)->createQueryBuilder('a')
            ->select('SUM(a.total_ttc)')
            ->where('a.date_achat BETWEEN :start AND :end')
            ->andWhere('a.etat = :status')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->setParameter('status', 'received')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $dailyProfit = $dailyTotal - $dailyPurchases;

        // Récupérer les alertes d'expiration (seuil à 30 jours)
        $expiryAlerts = $stockRepo->findExpiryAlerts(30);

        return $this->render('pharmacy/index.html.twig', [
            //'title' => 'Rima-Para',
            'dailyTotal' => $dailyTotal,
            'monthlyTotal' => $monthlyTotal,
            'yearlyTotal' => $yearlyTotal,
            'dailyProfit' => $dailyProfit,
            'expiredProducts' => $expiryAlerts['expired'],
            'soonExpiringProducts' => $expiryAlerts['soon'],
        ]);


    }

}
