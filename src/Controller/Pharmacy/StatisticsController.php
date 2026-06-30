<?php

namespace App\Controller\Pharmacy;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pharmacy\Achat;
use App\Entity\Pharmacy\Vente;
use App\Entity\Pharmacy\Stock;
use Doctrine\ORM\EntityManagerInterface;

final class StatisticsController extends AbstractController
{
    #[Route('/pharmacy/statistics', name: 'app_pharmacy_statistics')]
    public function index(EntityManagerInterface $em): Response
    {

        // Current month start and end
        $monthStart = new \DateTime('first day of this month 00:00:00');
        $monthEnd   = new \DateTime('first day of next month 00:00:00');

        // Last month
        $lastMonthStart = (clone $monthStart)->modify('-1 month');
        $lastMonthEnd   = $monthStart;

        // Helper to get totals
        $getSalesTotal = function($start, $end) use ($em) {
            return $em->getRepository(Vente::class)
                ->createQueryBuilder('v')
                ->select('SUM(v.total_ttc)')
                ->where('v.date_vente BETWEEN :start AND :end')
                ->andWhere('v.etat = :status')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->setParameter('status', 'delivered')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        };

        $getPurchasesTotal = function($start, $end) use ($em) {
            return $em->getRepository(Achat::class)
                ->createQueryBuilder('a')
                ->select('SUM(a.total_ttc)')
                ->where('a.date_achat BETWEEN :start AND :end')
                ->andWhere('a.etat = :status')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->setParameter('status', 'received')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        };

        // Current month
        $currentSales   = $getSalesTotal($monthStart, $monthEnd);
        $currentPurchases = $getPurchasesTotal($monthStart, $monthEnd);
        $currentProfit  = $currentSales - $currentPurchases;

        // Previous month
        $prevSales   = $getSalesTotal($lastMonthStart, $lastMonthEnd);
        $prevPurchases = $getPurchasesTotal($lastMonthStart, $lastMonthEnd);
        $prevProfit  = $prevSales - $prevPurchases;

        // Sales vs Purchases comparison
        $salesChange = $prevSales ? (($currentSales - $prevSales) / $prevSales) * 100 : 0;
        $purchasesChange = $prevPurchases ? (($currentPurchases - $prevPurchases) / $prevPurchases) * 100 : 0;
        $profitChange = $prevProfit ? (($currentProfit - $prevProfit) / $prevProfit) * 100 : 0;

        // Top 5 selling products (by quantity)
        $topProducts = $em->getRepository(Vente::class)
            ->createQueryBuilder('v')
            ->join('v.venteItems', 'vi')
            ->join('vi.produit', 'p')
            ->select('p.designation as product, SUM(vi.quantite) as total_qty')
            ->where('v.date_vente BETWEEN :start AND :end')
            ->setParameter('start', $monthStart)
            ->setParameter('end', $monthEnd)
            ->groupBy('p.id')
            ->orderBy('total_qty', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Prepare chart data (last 7 days)
        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = (new \DateTime())->modify("-$i days")->setTime(0,0,0);
            $next = (clone $day)->modify('+1 day');
            $daySales = $em->getRepository(Vente::class)
                ->createQueryBuilder('v')
                ->select('SUM(v.total_ttc)')
                ->where('v.date_vente BETWEEN :start AND :end')
                ->andWhere('v.etat = :status')
                ->setParameter('start', $day)
                ->setParameter('end', $next)
                ->setParameter('status', 'delivered')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            $dailySales[$day->format('d/m')] = $daySales;
        }

        $dailySalesLabels = [];
        $dailySalesValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = (new \DateTime())->modify("-$i days")->setTime(0,0,0);
            $next = (clone $day)->modify('+1 day');
            $daySales = $em->getRepository(Vente::class)
                ->createQueryBuilder('v')
                ->select('SUM(v.total_ttc)')
                ->where('v.date_vente BETWEEN :start AND :end')
                ->andWhere('v.etat = :status')
                ->setParameter('start', $day)
                ->setParameter('end', $next)
                ->setParameter('status', 'delivered')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            $dailySalesLabels[] = $day->format('d/m');
            $dailySalesValues[] = $daySales;
        }


        return $this->render('pharmacy/statistics/index.html.twig', [
            'currentSales'    => $currentSales,
            'currentPurchases'=> $currentPurchases,
            'currentProfit'   => $currentProfit,
            'salesChange'     => $salesChange,
            'purchasesChange' => $purchasesChange,
            'profitChange'    => $profitChange,
            'topProducts'     => $topProducts,
            'dailySales'      => $dailySales,
            'dailySalesLabels' => $dailySalesLabels,
            'dailySalesValues' => $dailySalesValues,
        ]);
    }
}
