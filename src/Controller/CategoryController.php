<?php


namespace App\Controller;


use App\Entity\Category;
use App\Entity\Members;
use App\Entity\Products;
use App\Entity\Province;
use App\Entity\Shop;
use App\Entity\SubCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DefaultController
 * @package App\Controller
 * @Route("/")
 */
class CategoryController extends AbstractController
{

    private $trans;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->trans = $translator;
    }

    /**
     * @param $id
     * @Route("/category/{id}/{page}", name="front_category_shop", methods={"GET"}, requirements={"page"="\d+", "id"="\d+"})
     * @return Response
     */
    public function shopByCategory($id, $page=1)
    {
        $categories = $this->entityManager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);

        $categorySelected = $this->entityManager->getRepository(Category::class)->find($id);
        if (!$categorySelected){
            $this->redirectToRoute('front_index');
        }
        $shops = $this->entityManager->getRepository(Shop::class)->findByCategoryPaginated($categorySelected, $page, Shop::NUM_ITEM);

        $total = count($shops);

        return $this->render('frontend/shops/index.html.twig',
           [
               'categories' => $categories,
               'categorySelected' => $categorySelected,
               'shops' => $shops,
               'current_page' => $page,
               'page_count' => ceil($total/Shop::NUM_ITEM),
               'total' => $total
           ]);
    }

    /**
     * @param $category
     * @param $page
     * @param $sub_category
     * @Route("category/{id}/subcategory/{id_s}/{page}", name="front_sub_category_shop", methods={"GET"}, requirements={"page"="\d+", "id"="\d+", "id_s"="\d+"})
     * @return Response
     */
    public function shopBySubCategory($id, $id_s, $page=1)
    {
        $categories = $this->entityManager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);

        $categorySelected = $this->entityManager->getRepository(Category::class)->find($id);
        if (!$categorySelected){
            $this->redirectToRoute('front_index');
        }

        $subCategorySelected = $this->entityManager->getRepository(SubCategory::class)->find($id_s);
        if (!$subCategorySelected){
            $this->redirectToRoute('front_index');
        }

        $shops = $this->entityManager->getRepository(Shop::class)->findByCategoryAndSubCategoryPaginated($categorySelected, $subCategorySelected, $page, Shop::NUM_ITEM);

        $total = count($shops);

        return $this->render('frontend/shops/index.html.twig',
           [
               'categories' => $categories,
               'categorySelected' => $subCategorySelected,
               'shops' => $shops,
               'current_page' => $page,
               'page_count' => ceil($total/Shop::NUM_ITEM),
               'total' => $total
           ]);
    }

    /**
     * @param Request $request
     * @param $category
     * @param $city
     * @param $page
     * @Route(path="/search/{category}/{city}/{page}", name="search_shops_category_city", methods={"POST", "GET"}, requirements={"page"="\d+"})
     * @return Response
     */
    public function search(Request $request, $category, $city, $page=1)
    {
        $publication = $sorted = null;

        if ($request->get('publication') && !empty($request->get('publication'))){
            $publication = $request->get('publication');
        }
        if ($request->get('sorted') && !empty($request->get('sorted'))){
            $sorted = $request->get('sorted');
        }


        $categories = $this->entityManager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);

        $categorySelected = $this->entityManager->getRepository(Category::class)->findOneBy(['code' => strtolower($category)]);
        if (!$categorySelected){
            $this->redirectToRoute('front_index');
        }

        $subCategories = $this->entityManager->getRepository(SubCategory::class)->findBy(['category' => $categorySelected, 'enabled'=>true], ['name' => 'ASC']);
        $shops = $this->entityManager->getRepository(Shop::class)->findByCategoryAndCityPaginated($categorySelected, $city, $publication, $sorted, $page, Shop::NUM_ITEM);

        $total = count($shops);

        return $this->render('frontend/shops/search-category.html.twig',
            [
                'categories' => $categories,
                'categorySelected' => $categorySelected,
                'subCategories' => $subCategories,
                'shops' => $shops,
                'city' => $city,
                'current_page' => $page,
                'page_count' => ceil($total/Shop::NUM_ITEM),
                'total' => $total
            ]);
    }

    /**
     * @param $category
     * @param $page
     * @param $sub_category
     * @Route("search/{page}", name="search_shops_sub_category_city", methods={"POST", "GET"}, requirements={"page"="\d+"})
     * @return Response
     */
    public function shopBySubCategoryAndCity(Request $request, $page=1)
    {
        $publication = $sorted = $category = $obj = $city= null;

        if ($request->get('category') && !empty($request->get('category'))){
            $category = $request->get('category');
        }
        if ($request->get('city') && !empty($request->get('city'))){
            $city = $request->get('city');
        }
        if ($request->get('obj') && !empty($request->get('obj'))){
            $obj = $request->get('obj');
        }
        if ($request->get('publication') && !empty($request->get('publication'))){
            $publication = $request->get('publication');
        }
        if ($request->get('sorted') && !empty($request->get('sorted'))){
            $sorted = $request->get('sorted');
        }

        $categories = $this->entityManager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);

        $categorySelected = $this->entityManager->getRepository(Category::class)->findOneBy(['code' => $category]);

        $subCategorySelected = $this->entityManager->getRepository(SubCategory::class)->findOneBy(['code' => $category]);
        if (!$subCategorySelected && $categorySelected){
            $this->redirectToRoute('front_index');
        }

        $shops = $this->entityManager->getRepository(Shop::class)->findByCategoryAndSubCategoryAndCityPaginated($categorySelected, $subCategorySelected, $city, $publication, $sorted, $page, Shop::NUM_ITEM);

        $total = count($shops);

        return $this->render('frontend/shops/search-sub-category.html.twig',
            [
                'categories' => $categories,
                'categorySelected' => $categorySelected ?: $subCategorySelected,
                'shops' => $shops,
                'city' => $city,
                'current_page' => $page,
                'page_count' => ceil($total/Shop::NUM_ITEM),
                'total' => $total
            ]);
    }


    /**
     * @param Request $request
     * @param $id
     * @param $shop
     * @Route(path="/{shop}/{id}/{page}", name="single_shop", methods={"GET"}, requirements={"id"="\d+", "page"="\d+"})
     * @return Response
     */
    public function singleShop(Request $request, $shop, $id, $page=1)
    {

        $categories = $this->entityManager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);

        $shopSelected = $this->entityManager->getRepository(Shop::class)->findOneBy(['id' => $id, 'name' => strtolower($shop)]);
        if (!$shopSelected){
            return $this->redirectToRoute('front_index');
        }

        $images = array_diff(scandir(__DIR__.'/../../public/uploads/'.$shopSelected->getImages()), ['.', '..']);

        $products = $this->entityManager->getRepository(Products::class)->getByShopPaginated($shopSelected, $page, Shop::NUM_ITEM8PROFILE);

        $total = count($products);

        return $this->render('frontend/shops/single.html.twig',
            [
                'categories' => $categories,
                'shop' => $shopSelected,
                'images' => $images,
                'current_page' => $page,
                'products' => $products,
                'page_count' => ceil($total/Shop::NUM_ITEM),
                'total' => $total
            ]);

    }


    /**
     * @param Request $request
     * @param $id
     * @Route(path="/shop/details/{id}", name="single_product_details", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getSingleProduct(Request $request, $id)
    {
        /** @var Products $product */
        $product = $this->entityManager->getRepository(Products::class)->findOneBy(['id' => $id, 'enabled' => true]);
        if (!$product){
            return $this->redirectToRoute('front_index');
        }

        $shop = $product->getShop();
        if (!$shop){
            return $this->redirectToRoute('front_index');
        }

        $images = array_diff(scandir(__DIR__.'/../../public/uploads/products/'.$product->getImages()), ['.', '..']);
        $categories = $this->entityManager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);
        return $this->render('frontend/products/single.html.twig', [
            'shop' => $shop,
            'product' => $product,
            'images' => $images,
            'categories' => $categories,
        ]);
    }




}
