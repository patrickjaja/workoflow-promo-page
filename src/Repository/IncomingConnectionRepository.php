<?php

namespace App\Repository;

use App\Entity\IncomingConnection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IncomingConnection>
 *
 * @method IncomingConnection|null find($id, $lockMode = null, $lockVersion = null)
 * @method IncomingConnection|null findOneBy(array $criteria, array $orderBy = null)
 * @method IncomingConnection[]    findAll()
 * @method IncomingConnection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IncomingConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomingConnection::class);
    }

    /**
     * @return IncomingConnection[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ic')
            ->andWhere('ic.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ic.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndConnectionId(User $user, string $connectionId): ?IncomingConnection
    {
        return $this->createQueryBuilder('ic')
            ->andWhere('ic.user = :user')
            ->andWhere('ic.connectionId = :connectionId')
            ->setParameter('user', $user)
            ->setParameter('connectionId', $connectionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isConnectionIdUsedByOtherUser(string $connectionId, ?User $excludeUser = null): bool
    {
        $qb = $this->createQueryBuilder('ic')
            ->select('COUNT(ic.id)')
            ->where('ic.connectionId = :connectionId')
            ->setParameter('connectionId', $connectionId);

        if ($excludeUser) {
            $qb->andWhere('ic.user != :user')
               ->setParameter('user', $excludeUser);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}