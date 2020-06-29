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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DefaultController
 * @package App\Controller
 * @Route("/")
 */
class DefaultController extends AbstractController
{

    private $trans;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->trans = $translator;
    }

    /**
     * @Route("/", name="front_index", methods={"GET"})
     */
    public function index()
    {
        $categories = $this->entityManager->getRepository(Category::class)->findBySubCategories();
        $products = $this->entityManager->getRepository(Products::class)->findBy(['enabled'=>true], ['name' => 'ASC']);

        //last 6 adverts
        $shops = $this->entityManager->getRepository(Shop::class)->findBy(['validated'=>true], ['name' => 'ASC'], 10);
        $members = $this->entityManager->getRepository(Members::class)->getAgents();

        //special adverts
        $specialShop = $this->entityManager->getRepository(Shop::class)->find(1);

        //var_dump($specialAdvert);die();
        $provinces = $this->entityManager->getRepository(Province::class)->findBySomeIds();

        return $this->render('frontend/static/index.html.twig',
           [
               'categories' => $categories,
               'products' => $products,
               'shops' => $shops,
               'members' => $members,
               'cities' => $provinces,
               'specialShop' => $specialShop
           ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="front_language", methods={"POST"})
     */
    public function language(Request $request)
    {
        $routeName = $request->get('_current_route');
        $locale = isset($_POST['_locale']) ? $_POST['_locale'] : $request->getLocale();
        $params = $request->get('params');

        $pars = [];
        if (count($params) > 0){
            foreach ($params as $param){
                $data = explode('*', $param);
                if ($data[0] == '_locale'){
                    $pars[$data[0]] = $locale;
                }else{
                    $pars[$data[0]] = $data[1];
                }

            }
        }


        return $this->redirect($this->generateUrl($routeName, $pars));
    }

    /**
     * contact us
     * @Route(path="/contactus", name="contact_us", methods={"GET"})
     */
    public function contact()
    {
        return $this->render('frontend/static/contact.html.twig');
    }

}
