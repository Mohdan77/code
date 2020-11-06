<?php


namespace Postroyka\ApiBundle\Controller;


use App\Postroyka\ApiBundle\Libs\Onliner;
use Postroyka\AppBundle\Controller\AbstractController;
use Postroyka\AppBundle\Provider\CatalogProvider;
use Submarine\PagesBundle\Manager\PagesManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends AbstractController
{
    public function yandexCartAction(Request $request, $id)
    {
        $product = $this->pages()->get($id);

        if (!$product->getId() || (CatalogProvider::PRODUCT_TYPE !== $product->getType()->getId())) {
            throw $this->createNotFoundException();
        }

        $this->cartProvider()->addToCart($this->getUser(), $product, 1);

        return $this->redirectToRoute('cart');
    }

    public function onlinerAction()
    {
        $onliner = new Onliner($this->getPageManager());
        $result = $onliner->updatePosition();

        return new Response($result, 200);
    }

    /**
     * @return PagesManager
     */
    public function getPageManager()
    {
        return $this->get('submarine.pages.pages_manager');
    }

}