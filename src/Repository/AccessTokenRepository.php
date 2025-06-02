<?php

namespace App\Repository;

use App\Entity\AccessToken;
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

    public function findByUserAndService(User $user, string $service): ?AccessToken
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.service = :service')
            ->setParameter('user', $user)
            ->setParameter('service', $service)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.service', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndService(string $organizationName, string $service): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->andWhere('u.organizationName = :organizationName')
            ->andWhere('a.service = :service')
            ->setParameter('organizationName', $organizationName)
            ->setParameter('service', $service)
            ->getQuery()
            ->getResult();
    }
}