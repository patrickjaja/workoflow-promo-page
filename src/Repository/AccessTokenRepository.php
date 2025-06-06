<?php

namespace App\Repository;

use App\Entity\AccessToken;
use App\Entity\ServiceIntegration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessToken>
 */
class AccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessToken::class);
    }

    public function findByServiceIntegration(ServiceIntegration $serviceIntegration): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.serviceIntegration = :serviceIntegration')
            ->setParameter('serviceIntegration', $serviceIntegration)
            ->getQuery()
            ->getResult();
    }

    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.serviceIntegration', 'si')
            ->andWhere('si.user = :user')
            ->setParameter('user', $user)
            ->orderBy('si.service', 'ASC')
            ->addOrderBy('si.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndService(string $organizationName, string $service): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.serviceIntegration', 'si')
            ->join('si.user', 'u')
            ->andWhere('u.organizationName = :organizationName')
            ->andWhere('si.service = :service')
            ->setParameter('organizationName', $organizationName)
            ->setParameter('service', $service)
            ->getQuery()
            ->getResult();
    }
}