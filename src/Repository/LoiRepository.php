<?php

namespace App\Repository;

use App\Entity\Loi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Loi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Loi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Loi[]    findAll()
 * @method Loi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loi::class);
    }

    public function findByArticleAndOrderByLoi($id)
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.articles', 'a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->orderBy('l.nomLoi')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Loi[] Returns an array of Loi objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Loi
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
