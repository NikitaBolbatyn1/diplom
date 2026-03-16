<?php

namespace App\Repository;

use App\Entity\ArchiveFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArchiveFile>
 */
class ArchiveFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArchiveFile::class);
    }

    public function findAllOrderedByDownloadedAt(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.downloadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
