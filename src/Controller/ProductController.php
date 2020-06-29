<?php


namespace App\Controller;


use App\Entity\Category;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Members;
use App\Entity\Products;
use App\Entity\Province;
use App\Entity\Quartier;
use App\Entity\Region;
use App\Entity\Shop;
use App\Entity\SubCategory;
use App\utils\Util;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DefaultController
 * @package App\Controller
 * @Route("/myproducts")
 */
class ProductController extends AbstractController
{

    private $trans;
    private $entity_manager;
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->trans = $translator;
        $this->entity_manager = $entityManager;
    }

    const EXTENSIONS = ['jpg', 'png', 'jpeg'];

    /**
     * @Route(path="/{id}/new-product/", name="new_product", methods={"GET", "POST"})
     */
    public function add(Request $request, $id)
    {

        $product = new Products();
        $member = $this->getUser();

        /** @var Shop $shop */
        $shop = $this->entity_manager->getRepository(Shop::class)->findOneBy(['id' => intval($id), 'member'=>$member]);
        if (!$shop){
            return $this->redirectToRoute('profile_single_shop_details', ['id' =>$id ]);
        }
        if ($request->isMethod(Request::METHOD_POST) && isset($_POST['products'])){
            $productsPost = $_POST['products'];

            $product->setName(isset($productsPost['name']) ? $productsPost['name'] : null);
            $product->setReference(substr(uniqid(), 1, 6));
            $product->setCategory($this->getCategory(isset($productsPost['category']) ? intval($productsPost['category']) : 0));
            $product->setSubCategory($this->getSubCategory(isset($productsPost['subcategory']) ? intval($productsPost['subcategory']) : 0));
            $product->setDescription(isset($productsPost['description']) ? $productsPost['description'] : null);
            $product->setPrice(isset($productsPost['price']) ? $productsPost['price'] : null);

            $product->setShop($shop);
            $dirName = time().rand(0000,9999);
            $product->setImages($dirName);

            try{

                $product->setSeoUrl(Util::slugify($product->getName()));
                $product->setSeoTitle(Util::slugify($product->getName()));
                $product->setSeoDescription(isset($productsPost['seoDescription']) ? $productsPost['seoDescription'] : null);

                $this->entity_manager->persist($product);
                $this->entity_manager->flush();

                $countFiles = count($_FILES['prooducts']['name']);
                $uploadsDir = $this->getParameter('uploads_directory_products');
                $newDirName = $uploadsDir.$dirName;
                if (!file_exists($newDirName)) {
                    mkdir($newDirName, 0777, true);
                }

                for($i=0; $i< $countFiles; $i++)
                {
                    $fileName = basename($_FILES['prooducts']['name'][$i]);
                    $targetFilePath = $newDirName . '/' .$fileName;
                    $explodeFileName = explode('.', $fileName);
                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                    if (count($explodeFileName) > 2){
                        throw new \Exception('file.invalid');
                    }

                    if (!in_array($fileType, self::EXTENSIONS)){
                        throw new \Exception('file.extension.invalid');
                    }
                    $targetFilePath = $newDirName . '/' .$i.'.png';
                    move_uploaded_file($_FILES["prooducts"]["tmp_name"][$i], $targetFilePath);
                }


                return $this->redirectToRoute('profile_single_shop_details', ['id' => $id]);
            }catch (\Exception $e){
                $this->addFlash('error', $e->getMessage());
            }

        }

        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);
        return $this->render('frontend/products/new.html.twig', [
            'categories' => $categories,
            'shop' => $shop,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @Route(path="/details/{id}", name="profile_single_product_details", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getSingleProduct(Request $request, $id)
    {
        /** @var Members $member */
        $member = $this->getUser();
        /** @var Products $product */
        $product = $this->entity_manager->getRepository(Products::class)->findOneBy(['id' => $id, 'enabled' => true]);
        if (!$product){
            return $this->redirectToRoute('front_profile_shops');
        }

        $shop = $product->getShop();

        if ($shop->getMember()->getId() != $member->getId()){
            return $this->redirectToRoute('front_profile_shops');
        }

        $images = array_diff(scandir(__DIR__.'/../../public/uploads/products/'.$product->getImages()), ['.', '..']);
        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);
        return $this->render('frontend/products/profile_single.html.twig', [
            'shop' => $shop,
            'product' => $product,
            'images' => $images,
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @Route(path="/update/{id}", name="profile_update_product", methods={"GET", "POST"}, requirements={"id"="\d+"})
     */
    public function updateSingleProduct(Request $request, $id)
    {
        /** @var Members $member */
        $member = $this->getUser();
        /** @var Products $product */
        $product = $this->entity_manager->getRepository(Products::class)->findOneBy(['id' => $id, 'enabled' => true]);
        if (!$product){
            return $this->redirectToRoute('front_profile_shops');
        }

        $shop = $product->getShop();

        if ($shop->getMember()->getId() != $member->getId()){
            return $this->redirectToRoute('front_profile_shops');
        }

        $images = array_diff(scandir(__DIR__.'/../../public/uploads/products/'.$product->getImages()), ['.', '..']);
        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);
        $subCategories = $this->entity_manager->getRepository(SubCategory::class)->findBy(['enabled'=>true, 'category' =>$product->getCategory()], ['name' => 'ASC']);
        return $this->render('frontend/products/update.html.twig', [
            'shop' => $shop,
            'product' => $product,
            'images' => $images,
            'categories' => $categories,
            'subCategories' => $subCategories,
        ]);
    }

    /**
     * @param $id
     * @return Category|null
     */
    private function getCategory($id){
        return $this->entity_manager->getRepository(Category::class)->find($id);
    }
    /**
     * @param $id
     * @return Region|null
     */
    private function getSubCategory($id){
        return $this->entity_manager->getRepository(SubCategory::class)->find($id);
    }


}
