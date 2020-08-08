<?php

namespace App\Repository;

use App\Entity\Debat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Debat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Debat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Debat[]    findAll()
 * @method Debat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DebatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Debat::class);
    }

    public function findByArticleAndOrderByDateSeance($id)
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.article', 'a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->orderBy('d.dateSeance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Debat[] Returns an array of Debat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Debat
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
