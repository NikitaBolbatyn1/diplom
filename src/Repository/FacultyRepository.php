<?php

namespace App\Repository;

use App\Entity\Faculty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faculty>
 */
class FacultyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faculty::class);
    }

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT
                f.id,
                f.name,
                f.head_name,
                COUNT(e.id) as total_events,
                COUNT(DISTINCT DATE(e.date)) as days_with_events,
                COUNT(DISTINCT e.responsible) as unique_responsibles,
                MIN(e.date) as first_event,
                MAX(e.date) as last_event
            FROM faculty f
            LEFT JOIN event e ON e.faculty_id = f.id
            GROUP BY f.id, f.name, f.head_name
            ORDER BY f.name ASC
        ';

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }
}
