<?php

namespace App\Repository;

use App\Entity\ServiceIntegration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceIntegration>
 */
class ServiceIntegrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceIntegration::class);
    }

    public function findByUserAndService(User $user, string $service): array
    {
        return $this->createQueryBuilder('si')
            ->andWhere('si.user = :user')
            ->andWhere('si.service = :service')
            ->setParameter('user', $user)
            ->setParameter('service', $service)
            ->orderBy('si.isDefault', 'DESC')
            ->addOrderBy('si.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDefaultByUserAndService(User $user, string $service): ?ServiceIntegration
    {
        return $this->createQueryBuilder('si')
            ->andWhere('si.user = :user')
            ->andWhere('si.service = :service')
            ->andWhere('si.isDefault = true')
            ->setParameter('user', $user)
            ->setParameter('service', $service)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('si')
            ->andWhere('si.user = :user')
            ->setParameter('user', $user)
            ->orderBy('si.service', 'ASC')
            ->addOrderBy('si.isDefault', 'DESC')
            ->addOrderBy('si.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function setDefaultForUserAndService(User $user, string $service, ServiceIntegration $defaultIntegration): void
    {
        $qb = $this->createQueryBuilder('si');
        $qb->update()
            ->set('si.isDefault', 'false')
            ->where('si.user = :user')
            ->andWhere('si.service = :service')
            ->andWhere('si.id != :integrationId')
            ->setParameter('user', $user)
            ->setParameter('service', $service)
            ->setParameter('integrationId', $defaultIntegration->getId())
            ->getQuery()
            ->execute();

        $defaultIntegration->setIsDefault(true);
        $this->getEntityManager()->persist($defaultIntegration);
        $this->getEntityManager()->flush();
    }
}