<?php

namespace App\Repository;

use App\Entity\OrganizationMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationMember>
 */
class OrganizationMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationMember::class);
    }

    public function findByOrganization(string $organizationName): array
    {
        return $this->createQueryBuilder('om')
            ->where('om.organizationName = :organizationName')
            ->setParameter('organizationName', $organizationName)
            ->orderBy('om.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndEmail(string $organizationName, string $email): ?OrganizationMember
    {
        return $this->createQueryBuilder('om')
            ->where('om.organizationName = :organizationName')
            ->andWhere('om.email = :email')
            ->setParameter('organizationName', $organizationName)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByInvitationToken(string $token): ?OrganizationMember
    {
        return $this->createQueryBuilder('om')
            ->where('om.invitationToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByOrganization(string $organizationName): array
    {
        return $this->createQueryBuilder('om')
            ->where('om.organizationName = :organizationName')
            ->andWhere('om.status = :status')
            ->setParameter('organizationName', $organizationName)
            ->setParameter('status', OrganizationMember::STATUS_ACTIVE)
            ->orderBy('om.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingByOrganization(string $organizationName): array
    {
        return $this->createQueryBuilder('om')
            ->where('om.organizationName = :organizationName')
            ->andWhere('om.status = :status')
            ->setParameter('organizationName', $organizationName)
            ->setParameter('status', OrganizationMember::STATUS_PENDING)
            ->orderBy('om.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByOrganization(string $organizationName): int
    {
        return $this->createQueryBuilder('om')
            ->select('COUNT(om.id)')
            ->where('om.organizationName = :organizationName')
            ->setParameter('organizationName', $organizationName)
            ->getQuery()
            ->getSingleScalarResult();
    }
}