<?php

namespace App\Repository\TradingBot;

use App\Entity\TradingBot\MarketSnapshot;
//use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
//use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class MarketSnapshotRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(MarketSnapshot::class));
    }

    public function findLatestTwo(string $symbol): array
    {
        return $this->findBy(
            ['symbol' => $symbol],
            ['timestamp' => 'DESC', 'id' => 'DESC'],
            2
        );
    }

    public function findLatest(string $symbol): ?MarketSnapshot
    {
        return $this->findOneBy(
            ['symbol' => $symbol],
            ['timestamp' => 'DESC']
        );
    }
}
