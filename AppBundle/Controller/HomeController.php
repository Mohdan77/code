<?php

namespace Postroyka\AppBundle\Controller;

use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Filter\PagesFilter;
use Submarine\PagesBundle\Manager\PagesManager;
use Submarine\PagesBundle\Manager\PageTypesManager;
use Submarine\UsersBundle\Manager\UsersManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    const HOME_TAG = 'home';
    const SLIDER_NUMBER = 10;
    const STOCK_NUMBER = 12;
    const PRODUCT_KEY = 'product';
    const MOBILE_SLIDER_TAG = 'mobile_slider';
    const TEMP_POPUP_TAG = 'temp_popup';

    public $cities = 'Бреста, Барановичей, Берёзы, Ганцевич, Дрогичина, Жабинки, Иваново, Ивацевичей, Кобрина, Пинска, Пружан, Столина, Витебска, Браслава, Верхнедвинска, Глубокого, Лепеля, Орши, Полоцка, Постав, Толочина, Гомеля, Жлобина, Калинковичей, Лоева, Мозыря, Речицы, Рогачева, Гродно, Волковыска, Ивье, Лиды, Новогрудка, Островца, Ошмян, Слонима, Щучина, Минска, Березинска, Боровлян, Борисова, Вилейки, Воложина, Дзержинска, Клецка, Логойска, Молодечнено, Мяделя, Несвижа, Пуховичей, Слуцка, Смолевич, Солигорска, Столбцов, Ракова, Марьной горки, Узды, Могилева, Бобруйска, Быхова, Климовичей, Костюковичей, Кричева, Мстиславля, Осиповичей, Хотимска, Шклова';



    public function homeAction()
    {
        $page = $this->pages()->getByTag(self::HOME_TAG);

        $this->setMetaTags($this->pages()->createMetaTag($page));



        $data = [
            'page' => $page,
            'left_menu' => $this->getCatalogGroups(),
            'slider' => $this->getSlider(),
            'mobile_slider' => $this->pages()->getByTag(self::MOBILE_SLIDER_TAG),
            'temp_popup' => $this->pages()->getByTag(self::TEMP_POPUP_TAG),
            'stock' => $this->getStock(),
            'novelty' =>$this->getNovelty(),
            'salesleaders' =>$this->getSalesLeaders(),
            'time' => time()
        ];

        return $this->renderPage('@PostroykaApp/Home/home.html.twig', $data);
    }


    public function actAction(Request $request)
    {
        if (!$request->request->all()) return new Response('Ничего', 200);

        /**
         *  Разнести по классам
         */
        $productType = $this->getPageTypes()->get('Product');
        $result['pages'] = $this->getPageManager()->searchTypePages($productType)->setMaxResults(300)->getArrayResult();
        $result['cities_top'][] = 'Минска';
        $result['cities_low'] = explode(', ', 'Бреста, Барановичей, Берёзы, Ганцевич, Дрогичина, Жабинки, Иваново, Ивацевичей, Кобрина, Пинска, Пружан, Столина, Витебска, Браслава, Верхнедвинска, Глубокого, Лепеля, Орши, Полоцка, Постав, Толочина, Гомеля, Жлобина, Калинковичей, Лоева, Мозыря, Речицы, Рогачева, Гродно, Волковыска, Ивье, Лиды, Новогрудка, Островца, Ошмян, Слонима, Щучина, Березинска, Борисова, Вилейки, Воложина, Клецка, Молодечнено, Мяделя, Несвижа, Пуховичей, Слуцка, Смолевич, Солигорска, Столбцов, Марьной горки, Узды, Могилева, Бобруйска, Быхова, Климовичей, Костюковичей, Кричева, Мстиславля, Осиповичей, Хотимска, Шклова, Жданович, Хатежино, Ратомки, Мачулищ, Боровлян, Заславля, Лагойска, Силич, Колодищ, Сеницы, Малиновки, Сухарево, Каменной горки, Тарасово, Чижовки, Брилевич, Курасовщины, Кунцевщины, Уручья, Зелёного луга, Шабанов, Лошицы, Смилович, Жодино, Дзержинска');

        return new JsonResponse($result);
    }


    /**
     * Слайдер
     * @return Page[]
     * @throws \Exception
     */
    private function getSlider()
    {
        $filter = new PagesFilter('slide');
        $filter->setMaxResults(self::SLIDER_NUMBER);
        $slides = $this->pages()->getChildPagesByTag(self::HOME_TAG, $filter);
        $slider = [];

        foreach ($slides as $slide) {
            $slider[$slide->getId()]['slide'] = $slide;

            // Для ссылок с якорем
            $url = $slide->getValue(self::PRODUCT_KEY);
            $url = explode('#', $url);

            $product = $this->pages()->getByUrl($url[0]);

            if ($product->getId()) {
                $slider[$slide->getId()]['product'] = $product;
                $slider[$slide->getId()]['anchor'] = !empty($url[1]) ? '#' . $url[1] : '';
            }
        }

        return $slider;
    }

    /**
     * Продукты на акции
     * @return Page[]
     */
    private function getStock()
    {
        $query = $this->catalog()->getStockAndMarkdownQuery();
        $query->setMaxResults(self::STOCK_NUMBER);

        return $query->getResult();
    }

    private function getNovelty()
    {
        $query = $this->catalog()->getNoveltyQuery();
        $query->setMaxResults(self::STOCK_NUMBER);

        return $query->getResult();
    }
    private function getSalesLeaders()
    {
        $query = $this->catalog()->getSalesLeadersQuery();
        $query->setMaxResults(self::STOCK_NUMBER);

        return $query->getResult();
    }

    /**
     * @return UsersManager
     */
    private function userManager()
    {
        return $this->get('submarine.users.manager');
    }


    /**
     * @return PageTypesManager
     */
    private function getPageTypes()
    {
        return $this->get('submarine.pages.type_manager');
    }

    /**
     * @return PagesManager
     */
    public function getPageManager()
    {
        return $this->get('submarine.pages.pages_manager');
    }

}
