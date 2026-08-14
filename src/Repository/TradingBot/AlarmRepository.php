<?php

namespace App\Repository\TradingBot;

use App\Entity\TradingBot\Alarm;
//use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
//use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class AlarmRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Alarm::class));
    }

    public function findLatest(string $symbol): ?Alarm
    {
        return $this->findOneBy(
            ['symbol' => $symbol],
            ['createdAt' => 'DESC', 'id' => 'DESC']
        );
    }

    public function findLatestHistory(string $symbol, int $limit = 50): array
    {
        return $this->findBy(
            ['symbol' => $symbol],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    public function countToday(string $symbol, \DateTimeInterface $today): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :today')
            ->andWhere('a.symbol = :symbol')
            ->setParameter('today', $today)
            ->setParameter('symbol', $symbol)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
