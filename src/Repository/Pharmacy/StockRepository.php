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



namespace App\Repository\Pharmacy;

use App\Entity\Pharmacy\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * Retourne les produits expirés et ceux dont la date de péremption approche.
     *
     * @param int $daysBeforeThreshold Nombre de jours avant l'expiration pour déclencher une alerte (ex: 30)
     * @return array ['expired' => array, 'soon' => array]
     */
    public function findExpiryAlerts(int $daysBeforeThreshold = 30): array
    {
        $now = new \DateTimeImmutable();
        $thresholdDate = $now->modify("+{$daysBeforeThreshold} days");

        // 1. Produits déjà expirés
        $expired = $this->createQueryBuilder('s')
            ->where('s.date_expiration IS NOT NULL')
            ->andWhere('s.date_expiration < :now')
            ->setParameter('now', $now)
            ->orderBy('s.date_expiration', 'ASC')
            ->getQuery()
            ->getResult();

        // 2. Produits proches de l'expiration (entre aujourd'hui et thresholdDate)
        $soon = $this->createQueryBuilder('s')
            ->where('s.date_expiration IS NOT NULL')
            ->andWhere('s.date_expiration >= :now')
            ->andWhere('s.date_expiration <= :threshold')
            ->setParameter('now', $now)
            ->setParameter('threshold', $thresholdDate)
            ->orderBy('s.date_expiration', 'ASC')
            ->getQuery()
            ->getResult();

        return ['expired' => $expired, 'soon' => $soon];
    }

//    /**
//     * @return Stock[] Returns an array of Stock objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Stock
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
