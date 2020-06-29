<?php

namespace App\Controller\Admin;

use App\Entity\Shop;
use App\Form\ShopType;
use App\Repository\ShopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/shop")
 */
class ShopController extends AbstractController
{
    /**
     * @param  ShopRepository $shopRepository
     * @Route("/", name="shop_index", methods={"GET"})
     * @return Response
     */
    public function index(ShopRepository $shopRepository): Response
    {
        return $this->render('backend/shop/index.html.twig', [
            'shops' => $shopRepository->findAll(),
        ]);
    }

    /**
     * @param Shop $shop
     * @Route("/{id}", name="shop_show", methods={"GET"})
     * @return
     */
    public function show(Shop $shop): Response
    {
        return $this->render('backend/shop/show.html.twig', [
            'shop' => $shop,
        ]);
    }


    /**
     * @Route("/{id}", name="shop_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Shop $shop): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shop->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($shop);
            $entityManager->flush();
        }

        return $this->redirectToRoute('shop_index');
    }
}
