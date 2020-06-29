<?php

namespace App\Repository;

use App\Entity\Products;
use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }


    public function getByShopPaginated($shop, $page, $maxPerPage = Shop::NUM_ITEM8PROFILE)
    {
        $query =  $this->createQueryBuilder('p')
            ->andWhere('p.shop = :shop')
            ->setParameter('shop', $shop)
            ->orderBy('p.id', 'ASC')
            ->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        return new Paginator($query);
    }


    /*
    public function findOneBySomeField($value): ?Products
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
