<?php

namespace App\Repository\TradingBot;

use App\Entity\TradingBot\BotStatus;
//use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
//use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class BotStatusRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(BotStatus::class));
    }

    public function findLatest(): ?BotStatus
    {
        return $this->findOneBy(
            [],
            ['createdAt' => 'DESC']
        );
    }
}
