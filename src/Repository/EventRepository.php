<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findByUser(User $user)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllForExport(?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.createdBy', 'u')
            ->addSelect('u');

        if ($start) {
            $qb->andWhere('e.date >= :start')->setParameter('start', $start);
        }
        if ($end) {
            $qb->andWhere('e.date <= :end')->setParameter('end', $end);
        }

        return $qb->orderBy('e.date', 'DESC')->getQuery()->getResult();
    }
}
