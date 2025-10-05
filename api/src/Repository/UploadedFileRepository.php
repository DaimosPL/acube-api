<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UploadedFile;
use App\Enum\FileStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UploadedFile>
 */
class UploadedFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadedFile::class);
    }

    /**
     * @return UploadedFile[]
     */
    public function findNewFiles(int $limit): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.status = :status')
            ->setParameter('status', FileStatus::NEW->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function lockFileForProcessing(UploadedFile $file): void
    {
        $file->setStatus(FileStatus::PROCESSING);
        $file->setLastError(null);
        $this->getEntityManager()->flush();
    }

    public function markFileProcessed(UploadedFile $file): void
    {
        $file->setStatus(FileStatus::PROCESSED);
        $file->setLastError(null);
        $this->getEntityManager()->flush();
    }

    public function markFileFailed(UploadedFile $file, string $error): void
    {
        $file->setStatus(FileStatus::FAILED);
        $file->setLastError($error);
        $this->getEntityManager()->flush();
    }

    public function markNotificationSent(UploadedFile $file): void
    {
        $file->setNotificationStatus(\App\Enum\NotificationStatus::SENDED);
        $this->getEntityManager()->flush();
    }

    public function setNotificationStatus(UploadedFile $file, \App\Enum\NotificationStatus $status): void
    {
        $file->setNotificationStatus($status);
        $this->getEntityManager()->flush();
    }
}
