<?php

namespace App\Repository;

use App\Entity\TeamsId;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamsId>
 */
class TeamsIdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamsId::class);
    }

    /**
     * Find all Teams IDs for a user
     * @return TeamsId[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.isPrimary', 'DESC')
            ->addOrderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a Teams ID by user and teams ID value
     */
    public function findByUserAndTeamsId(User $user, string $teamsId): ?TeamsId
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.teamsId = :teamsId')
            ->setParameter('user', $user)
            ->setParameter('teamsId', $teamsId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find primary Teams ID for a user
     */
    public function findPrimaryByUser(User $user): ?TeamsId
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.isPrimary = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if a Teams ID is already used by another user
     */
    public function isTeamsIdUsedByOtherUser(string $teamsId, ?User $excludeUser = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.teamsId = :teamsId')
            ->setParameter('teamsId', $teamsId);

        if ($excludeUser !== null) {
            $qb->andWhere('t.user != :excludeUser')
                ->setParameter('excludeUser', $excludeUser);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}