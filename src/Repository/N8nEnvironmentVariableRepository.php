<?php

namespace App\Repository;

use App\Entity\N8nEnvironmentVariable;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<N8nEnvironmentVariable>
 */
class N8nEnvironmentVariableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, N8nEnvironmentVariable::class);
    }

    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.variableName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndVariableName(User $user, string $variableName): ?N8nEnvironmentVariable
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.variableName = :variableName')
            ->setParameter('user', $user)
            ->setParameter('variableName', $variableName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getVariablesByUserAsArray(User $user): array
    {
        $variables = $this->findAllByUser($user);
        $result = [];
        
        foreach ($variables as $variable) {
            $result[$variable->getVariableName()] = $variable->getVariableValue();
        }
        
        return $result;
    }
}