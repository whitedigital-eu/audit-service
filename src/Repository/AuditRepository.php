<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WhiteDigital\Audit\Entity\Audit;

/**
 * @method Audit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Audit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Audit[]    findAll()
 * @method Audit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Audit::class);
    }
}
