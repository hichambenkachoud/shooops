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
 * @Route("/shop")
 */
class ShopController extends AbstractController
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
     * @Route(path="/new-shop/", name="new_shop", methods={"GET", "POST"})
     */
    public function add(Request $request)
    {

        $shop = new Shop();

        if ($request->isMethod(Request::METHOD_POST) && isset($_POST['shops'])){
            $member = $this->getUser();
            $shopsPost = $_POST['shops'];

            $shop->setAddress(isset($shopsPost['address']) ? $shopsPost['address'] : null);
            $shop->setRegion($this->getRegion(isset($shopsPost['region']) ? intval($shopsPost['region']) : 0));
            $shop->setCity($this->getCity(isset($shopsPost['city']) ? intval($shopsPost['city']) : 0));
            $shop->setProvince($this->getProvince(isset($shopsPost['province']) ? intval($shopsPost['province']) : 0));
            $shop->setQuartier($this->getQuartier(isset($shopsPost['quartier']) ? intval($shopsPost['quartier']) : 0));
            $shop->setDescription(isset($shopsPost['description']) ? $shopsPost['description'] : null);
            $shop->setName(isset($shopsPost['title']) ? $shopsPost['title'] : null);
            $shop->setTelephone(isset($shopsPost['tel']) ? substr($shopsPost['tel'], 4) : null);
            $shop->setWhatsapp(isset($shopsPost['whatsapp']) ? substr($shopsPost['whatsapp'], 4) : null);

            $shop->setLongitude(isset($shopsPost['longitude']) ? $shopsPost['longitude'] : null);
            $shop->setLatitude(isset($shopsPost['latitude']) ? $shopsPost['latitude'] : null);

            $shop->setMember($member);
            $dirName = time().rand(0000,9999);
            $shop->setImages($dirName);

            try{

                //$shop->setSeoUrl(Util::slugify($shop->getName()));
                $shop->setSeoTitle(Util::slugify($shop->getName()));
                $shop->setKeywords(isset($shopsPost['keywords']) ? $shopsPost['keywords'] : null);
                $shop->setSeoDescription(isset($shopsPost['seoDescription']) ? $shopsPost['seoDescription'] : null);

                $this->entity_manager->persist($shop);
                $this->entity_manager->flush();

                $countFiles = count($_FILES['shooops']['name']);
                $uploadsDir = $this->getParameter('uploads_directory');
                $newDirName = $uploadsDir.$dirName;
                if (!file_exists($newDirName)) {
                    mkdir($newDirName, 0777, true);
                }

                for($i=0; $i< $countFiles; $i++)
                {
                    $fileName = basename($_FILES['shooops']['name'][$i]);
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
                    move_uploaded_file($_FILES["shooops"]["tmp_name"][$i], $targetFilePath);
                }


                return $this->redirectToRoute('profile_single_shop_details', ['id' => $shop->getId()]);
            }catch (\Exception $e){
                $this->addFlash('error', $e->getMessage());
            }

        }

        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);
        $regions = $this->entity_manager->getRepository(Region::class)->findBy(['enabled'=>true]);
        return $this->render('frontend/shops/new.html.twig', [
            'regions' => $regions,
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @Route(path="/mon_profil/my-shops/{page}", name="front_profile_shops", methods={"GET"}, requirements={"page"="\d+"})
     */
    public function getMyShops(Request $request, $page = 1)
    {
        $member = $this->getUser();

        if ($request->get('page') && !empty($request->get('page'))){
            $page = intval($request->get('page'));
        }

        $shops = $this->entity_manager->getRepository(Shop::class)->findByMemberPaginated($member, $page,  Shop::NUM_ITEM);

        $total = count($shops);
        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);
        return $this->render('frontend/shops/my_shops.html.twig', [
            'shops' => $shops,
            'categories' => $categories,
            'current_page' => $page,
            'page_count' => ceil($total/Shop::NUM_ITEM),
            'total' => $total
        ]);
    }


    /**
     * @param Request $request
     * @Route(path="/mon_profil/{id}", name="front_profile_shops_update", methods={"GET", "POST"}, requirements={"id"="\d+"})
     */
    public function updateShop(Request $request, $id)
    {
        $member = $this->getUser();

        $shop = $this->entity_manager->getRepository(Shop::class)->findOneBy(['id'=>intval($id), 'member' => $member]);

        if (!$shop){
            return $this->redirectToRoute('front_profile_shops');
        }

        $images = array_diff(scandir(__DIR__.'/../../public/uploads/'.$shop->getImages()), ['.', '..']);

        if ($request->isMethod(Request::METHOD_POST) && isset($_POST['shops'])){
            $shopsPost = $_POST['shops'];

            $shop->setAddress(isset($shopsPost['address']) ? $shopsPost['address'] : null);
            $shop->setRegion($this->getRegion(isset($shopsPost['region']) ? intval($shopsPost['region']) : 0));
            $shop->setCity($this->getCity(isset($shopsPost['city']) ? intval($shopsPost['city']) : 0));
            $shop->setProvince($this->getProvince(isset($shopsPost['province']) ? intval($shopsPost['province']) : 0));
            $shop->setQuartier($this->getQuartier(isset($shopsPost['quartier']) ? intval($shopsPost['quartier']) : 0));
            $shop->setDescription(isset($shopsPost['description']) ? $shopsPost['description'] : null);
            $shop->setName(isset($shopsPost['title']) ? $shopsPost['title'] : null);
            $shop->setTelephone(isset($shopsPost['tel']) ? substr($shopsPost['tel'], 4) : null);
            $shop->setWhatsapp(isset($shopsPost['whatsapp']) ? substr($shopsPost['whatsapp'], 4) : null);

            $shop->setLongitude(isset($shopsPost['longitude']) ? $shopsPost['longitude'] : null);
            $shop->setLatitude(isset($shopsPost['latitude']) ? $shopsPost['latitude'] : null);

            $shop->setMember($member);


            try{

                //$shop->setSeoUrl(Util::slugify($shop->getName()));
                $shop->setSeoTitle(Util::slugify($shop->getName()));
                $shop->setKeywords(isset($shopsPost['keywords']) ? $shopsPost['keywords'] : null);
                $shop->setSeoDescription(isset($shopsPost['seoDescription']) ? $shopsPost['seoDescription'] : null);

                $this->entity_manager->flush();

                $countFiles = count($_FILES['shooops']['name']);
                $uploadsDir = $this->getParameter('uploads_directory');
                $newDirName = $uploadsDir.$shop->getImages();

                for($i=0; $i< $countFiles; $i++)
                {
                    $fileName = basename($_FILES['shooops']['name'][$i]);
                    if (empty($fileName)){
                        continue;
                    }

                    $targetFilePath = $newDirName . '/' .$fileName;
                    $explodeFileName = explode('.', $fileName);
                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                    if (count($explodeFileName) > 2){
                        throw new \Exception('file.invalid');
                    }

                    if (!in_array($fileType, self::EXTENSIONS)){
                        throw new \Exception('file.extension.invalid');
                    }

                    $targetFilePath = $newDirName . '/' .rand(0000,9999).'.png';
                    move_uploaded_file($_FILES["shooops"]["tmp_name"][$i], $targetFilePath);

                    if (!is_file($newDirName . '/0.png')){
                        rename($targetFilePath, $newDirName . '/0.png');
                    }
                }


                return $this->redirectToRoute('front_profile_shops');
            }catch (\Exception $e){
                $this->addFlash('error', $e->getMessage());
            }

        }

        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);
        $regions = $this->entity_manager->getRepository(Region::class)->findBy(['enabled'=>true]);
        $provinces = $this->entity_manager->getRepository(Province::class)->findBy(['enabled'=>true, 'region' => $shop->getRegion()]);
        $cities = $this->entity_manager->getRepository(City::class)->findBy(['enabled'=>true, 'province' => $shop->getProvince()]);
        $quartiers = $this->entity_manager->getRepository(Quartier::class)->findBy(['enabled'=>true, 'city' => $shop->getCity()]);

        return $this->render('frontend/shops/profile_single_shop.html.twig', [
            'shop' => $shop,
            'categories' => $categories,
            'regions' => $regions,
            'provinces' => $provinces,
            'cities' => $cities,
            'quartiers' => $quartiers,
            'images' => $images,
        ]);
    }
    /**
     * @param Request $request
     * @Route(path="/mon_profil/details/{id}", name="profile_single_shop_details", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function detailsShop(Request $request, $id)
    {
        $member = $this->getUser();

        $shop = $this->entity_manager->getRepository(Shop::class)->findOneBy(['id'=>intval($id), 'member' => $member]);

        if (!$shop){
            return $this->redirectToRoute('front_profile_shops');
        }

        $images = array_diff(scandir(__DIR__.'/../../public/uploads/'.$shop->getImages()), ['.', '..']);


        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);


        return $this->render('frontend/shops/profile_single_shop_details.html.twig', [
            'shop' => $shop,
            'categories' => $categories,
            'images' => $images,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route(path="/mon_profil/wishes/{page}", name="front_profile_wishes", methods={"GET"})
     */
    public function profileWishList(Request $request, $page = 1)
    {
        /** @var Members $member */
        $member = $this->getUser();
        if ($request->get('page') && !empty($request->get('page'))){
            $page = intval($request->get('page'));
        }
        $wishListArray = $shops = array();
        if (!empty($member->getWishList())){
            $wishListArray = json_decode($member->getWishList(), true);
            $shops = $this->entity_manager->getRepository(Shop::class)->findAllWishesBYMemberPaginated($wishListArray, $page, Shop::NUM_ITEM8PROFILE);
        }
        $total = count($shops);
        $categories = $this->entity_manager->getRepository(Category::class)->findBy(['enabled'=>true], ['name' => 'ASC']);

        return $this->render('frontend/shops/profile_wish.html.twig', [
            'shops' => $shops,
            'categories' => $categories,
            'page_count' => ceil($total/Shop::NUM_ITEM8PROFILE),
            'total' => $total,
            'current_page' => $page
        ]);
    }



    /**
     * @param $id
     * @return Region|null
     */
    private function getCountry(){
        return $this->entity_manager->getRepository(Country::class)->findOneBy(['isDefault' => true]);
    }
    /**
     * @param $id
     * @return Region|null
     */
    private function getRegion($id){
        return $this->entity_manager->getRepository(Region::class)->find($id);
    }
    /**
     * @param $id
     * @return City|null
     */
    private function getCity($id){
        return $this->entity_manager->getRepository(City::class)->find($id);
    }
    /**
     * @param $id
     * @return Province|null
     */
    private function getProvince($id){
        return $this->entity_manager->getRepository(Province::class)->find($id);
    }
    /**
     * @param $id
     * @return Quartier|null
     */
    private function getQuartier($id){
        return $this->entity_manager->getRepository(Quartier::class)->find($id);
    }




}
