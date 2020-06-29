<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Shop;
use App\Entity\SubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Shop|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shop|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shop[]    findAll()
 * @method Shop[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    /**
     * @param Category $category
     * @param $page
     * @param int $maxPerPage
     * @return Paginator
     */
    public function findByCategoryPaginated($category, $page, $maxPerPage = Shop::NUM_ITEM)
    {
        $q =  $this->createQueryBuilder('s')
            ->join('s.category', 'category')
            ->andWhere('(s.validated = :validated)')
            ->andWhere('category.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->setParameter('validated', true)
            ->orderBy('s.id', 'DESC')
            ->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        return new Paginator($q);
    }

    /**
     * @param Category $category
     * @param $page
     * @param $city
     * @param int $maxPerPage
     * @return Paginator
     */
    public function findByCategoryAndCityPaginated($category, $city, $publication, $sorted, $page, $maxPerPage = Shop::NUM_ITEM)
    {
        $currentDate = date('Y-m-d H:i:s');
        $query =  $this->createQueryBuilder('s')
            ->leftjoin('s.products', 'p')
            ->leftJoin('p.category', 'category')
            ->leftJoin('p.subCategory', 'subCategory')
            ->leftJoin('s.province', 'province')
            ->leftJoin('s.city', 'city')
            ->leftJoin('s.quartier', 'quartier')
            ->andWhere('(s.validated = :validated)')
            ->andWhere('(category.id = :categoryId OR subCategory.id = :categoryId)')
            ->setParameter('categoryId', $category->getId())
            ->setParameter('validated', true)
            ->andWhere('lower(province.code) LIKE :city OR lower(city.code) LIKE :city OR lower(quartier.code) LIKE :city')
            ->setParameter('city', "%".$city."%");


        if ($publication != '0' && $publication != null){
            $startDate = new \DateTime('-'.$publication.' day');
            $startDate = $startDate->format('Y-m-d H:i:s');
            $query->andWhere('s.createDate BETWEEN :startDate AND :endDate ')->setParameter('startDate', $startDate)->setParameter('endDate', $currentDate);
        }
        if ($sorted != '' && !empty($sorted)){
            $sortOptions = explode('-', $sorted);
            if (is_array($sortOptions) && count($sortOptions) == 2 && in_array($sortOptions[0], ['createDate'])  && in_array($sortOptions[1], ['desc', 'asc']) ){
                $query->orderBy('s.'.trim($sortOptions[0]), trim(strtoupper($sortOptions[1])));
            }
        }else{
            $query->orderBy('s.createDate', 'DESC');
        }


        $query->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        return new Paginator($query);
    }

    /**
     * @param Category $category
     * @param SubCategory $subCategory
     * @param $page
     * @param int $maxPerPage
     * @return Paginator
     */
    public function findByCategoryAndSubCategoryPaginated($category, $subCategory, $page, $maxPerPage = Shop::NUM_ITEM)
    {
        $q =  $this->createQueryBuilder('s')
            ->leftjoin('s.products', 'p')
            ->leftJoin('p.category', 'category')
            ->leftJoin('p.subCategory', 'subCategory')
            ->andWhere('(s.validated = :validated)')
            ->andWhere('category.id = :categoryId')
            ->andWhere('subCategory.id = :subCategoryId')
            ->setParameter('categoryId', $category->getId())
            ->setParameter('categoryId', $subCategory->getId())
            ->setParameter('validated', true)
            ->orderBy('s.id', 'DESC')
            ->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        return new Paginator($q);
    }

    /**
     * @param $category
     * @param $subCategory
     * @param $city
     * @param $publication
     * @param $sorted
     * @param $page
     * @param int $maxPerPage
     * @return Paginator
     * @throws \Exception
     */
    public function findByCategoryAndSubCategoryAndCityPaginated($category, $subCategory, $city, $publication, $sorted, $page, $maxPerPage = Shop::NUM_ITEM)
    {
        $currentDate = date('Y-m-d H:i:s');
        $query =  $this->createQueryBuilder('s')
            ->leftjoin('s.products', 'p')
            ->leftJoin('p.category', 'category')
            ->leftJoin('p.subCategory', 'subCategory')
            ->leftJoin('s.province', 'province')
            ->leftJoin('s.city', 'city')
            ->leftJoin('s.quartier', 'quartier')
            ->andWhere('(s.validated = :validated)')
            ->setParameter('validated', true)
            ->andWhere('lower(province.code) LIKE :city OR lower(city.code) LIKE :city OR lower(quartier.code) LIKE :city')
            ->setParameter('city', "%".$city."%");

        if ($category != null){
            $query ->andWhere('category.id = :categoryId')->setParameter('categoryId', $category->getId());
        }
        if ($subCategory != null) {
            $query ->andWhere('subCategory.id = :subCategoryId')->setParameter('subCategoryId', $subCategory->getId());
        }

        if ($publication != '0' && $publication != null){
            $startDate = new \DateTime('-'.$publication.' day');
            $startDate = $startDate->format('Y-m-d H:i:s');
            $query->andWhere('s.createDate BETWEEN :startDate AND :endDate ')->setParameter('startDate', $startDate)->setParameter('endDate', $currentDate);
        }
        if ($sorted != '' && !empty($sorted)){
            $sortOptions = explode('-', $sorted);
            if (is_array($sortOptions) && count($sortOptions) == 2 && in_array($sortOptions[0], ['createDate'])  && in_array($sortOptions[1], ['desc', 'asc']) ){
                $query->orderBy('s.'.trim($sortOptions[0]), trim(strtoupper($sortOptions[1])));
            }
        }else{
            $query->orderBy('s.createDate', 'DESC');
        }

        $query->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        return new Paginator($query);
    }


    public function findByMemberPaginated($member, $page, $maxPerPage = Shop::NUM_ITEM){

        $q =  $this->createQueryBuilder('s')
            ->andWhere('s.member = :member')
            ->andWhere('(s.validated = :validated)')
            ->setParameter('validated', 1)
            ->setParameter('member', $member)
            ->orderBy('s.id', 'DESC')
            ->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        return new Paginator($q);
    }


    public function findAllWishesBYMemberPaginated($wishList, $page, $maxPerPage = Shop::NUM_ITEM8PROFILE)
    {
        $q =  $this->createQueryBuilder('a')
            ->andWhere('a.id IN (:ids)')
            ->setParameter('ids', $wishList)
            ->orderBy('a.createDate', 'DESC')
            ->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        return new Paginator($q);
    }
}
