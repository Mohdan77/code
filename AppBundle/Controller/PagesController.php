<?php

namespace Postroyka\AppBundle\Controller;

use Postroyka\AppBundle\Form\DirectorForm;
use Postroyka\AppBundle\Form\FeedbackForm;
use Postroyka\AppBundle\Form\ReviewForm;
use Postroyka\AppBundle\Form\VacancyForm;
use Postroyka\AppBundle\Provider\CatalogProvider;
use Postroyka\AppBundle\ServicePages;
use Submarine\ControlsBundle\Twig\StringFormatExtension;
use Submarine\FrontBundle\Controller\Routing\DynamicRoutingInterface;
use Submarine\FrontBundle\Document\MetaTagContainer;
use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Entity\Relations;
use Submarine\PagesBundle\Filter\PagesFilter;
use Submarine\ReviewsBundle\Entity\Review;
use Submarine\ReviewsBundle\Manager\ReviewsManager;
use Submarine\ReviewsBundle\Provider\ReviewsProvider;
use Submarine\PagesBundle\Event\PageSearchQueryEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class PagesController extends AbstractController implements DynamicRoutingInterface
{
    const ARTICLES_GROUP_TYPE = 'articlesGroup';
    const ARTICLE_TYPE = 'article';
    const MANUFACTURER_TYPE = 'manufacturer';
    const ARTICLES_PER_PAGE = 8;
    const RELATED_NUMBER = 7;
    const SIMILAR_NUMBER = 4;
    const REVIEWS_PER_PAGE = 10;
    const REFERER_SESSION_KEY = 'referer';
    const POPULAR_NUMBER = 12;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        parent::__construct();

        $this->dispatcher = $dispatcher;
    }

    /**
     * @return ReviewsProvider
     */
    private function reviews()
    {
        return $this->get('submarine.reviews.provider');
    }

    /**
     * @return ReviewsManager
     */
    private function reviewsManager()
    {
        return $this->get('submarine.reviews.manager');
    }

    /**
     * @return Session
     */
    private function session()
    {
        return $this->get('session');
    }

    /**
     * @param Request $request
     * @param $url
     * @return mixed
     */
    public function loadUrlAction(Request $request, $url)
    {
        // Используем поиск чувствительный к регистру - SEO
        $page = $this->catalog()->getByUrl($url);

        if (!$page->getId()) {
            $filter = new PagesFilter();
            $filter->setField('old_url', '/' . $url);
            $page = current($this->pages()->filterQuery($filter)->setMaxResults(1)->getResult());

            if (!$page) {
                $filter->setField('old_url2', '/' . $url);
                $page = current($this->pages()->filterQuery($filter)->setMaxResults(1)->getResult());
            }

            if (!$page) {
                $filter->setField('old_url3', '/' . $url);
                $page = current($this->pages()->filterQuery($filter)->setMaxResults(1)->getResult());
            }

            // Редирект со старого URL страницы на новый
            if ($page && $page->getId()) {
                return $this->redirect($this->convertUrl($page->getUrl()), 301);
            }

            // Редирект с URL без слэша на конце на URL со слешем
            if (substr($url, -1) !== '/') {
                return $this->redirect($this->convertUrl($url . '/'), 301);
            }

            throw $this->createNotFoundException();
        }

        return $this->handlePage($page, $request);
    }

    /**
     * @param Request $request
     * @param $tag
     * @return mixed
     */
    public function loadTagAction(Request $request, $tag)
    {
        $page = $this->pages()->getByTag($tag);

        return $this->handlePage($page, $request);
    }

    /**
     * Обработка страницы
     * @param Page $page
     * @param Request $request
     * @return mixed
     */
    private function handlePage(Page $page, Request $request)
    {
        if (!$page->getId()) {
            throw $this->createNotFoundException();
        }

        $page->setSelected(true);

        $methodName = $page->getType()->getId() . 'Type';

        if (!method_exists($this, $methodName)) {
            throw $this->createNotFoundException();
        }

        $this->setMetaTags($this->pages()->createMetaTag($page));

        $data = [
            'page' => $page,
            'nav' => $this->getNavigation($page, $request),
        ];

        return $this->$methodName($data, $request);
    }

    /**
     * Хак для отображения правильной вложенности у продукта
     * @param Page $page
     * @param Request $request
     * @return Page[]
     * @var Page $parent
     */
    private function getNavigation(Page $page, Request $request)
    {
        if ($page->getType()->getId() === CatalogProvider::PRODUCT_TYPE) {
            $referer = $this->session()->get(self::REFERER_SESSION_KEY);
            $referer = explode('/', $referer);
            $referer = array_filter($referer);
            $referer = end($referer);

            $navigationPaths = $this->pages()->getNavigationPaths($page);

            if (count($navigationPaths) > 1) {
                foreach ($navigationPaths as $path) {
                    $parent = end($path);

                    if (stripos($parent->getUrl(), $referer) !== false) {
                        return $path;
                    }
                }
            }
        }

        return $this->pages()->getNavigationPath($page);
    }

    /**
     * Обычная страница
     */
    public function pageType($data, Request $request)
    {
        return $this->renderPage('@PostroykaApp/Pages/page.html.twig', $data);
    }

    /**
     * Статьи
     */
    public function articlesSectionType($data, Request $request)
    {
        $filter = new PagesFilter(self::ARTICLES_GROUP_TYPE);
        $data['groups'] = $this->pages()->getChildPages($data['page'], $filter);

        foreach ($data['groups'] as $group) {
            $data['child_count'][$group->getId()] = count($this->pages()->getChildPages($group));
        }

        return $this->renderPage('@PostroykaApp/Pages/articles_section.html.twig', $data);
    }

    /**
     * Раздел статей
     */
    public function articlesGroupType($data, Request $request)
    {
        $filter = new PagesFilter(self::ARTICLE_TYPE);
        $articles = $this->pages()->getChildPagesQuery($data['page'], $filter);
        $data['articles'] = $this->paginator()->paginate($articles, $request->get('page', 1), self::ARTICLES_PER_PAGE, ['wrap-queries' => true]);

        return $this->renderPage('@PostroykaApp/Pages/articles_group.html.twig', $data);
    }

    /**
     * Статья
     */
    public function articleType($data, Request $request)
    {
        // Сопутствующие товары
        $data['related'] = $this->getLinkedProducts($data['page'], self::RELATED_NUMBER);

        return $this->renderPage('@PostroykaApp/Pages/article.html.twig', $data);
    }

    /**
     * Контакты
     */
    public function contactsType($data, Request $request)
    {
        $form = $this->createForm(FeedbackForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->mailer()->sendForm($form, 'mail.subject.feedback', $request);

                return $this->redirectToRoute('feedback_success');
            } catch (\Exception $e) {
                $this->notifier()->error();
            }
        }

        $data['form'] = $form->createView();

        return $this->renderPage('@PostroykaApp/Pages/contacts.html.twig', $data);
    }

    /**
     * Форма обратной связи отправлена успешно
     */
    public function feedbackSuccessAction()
    {
        $data['page'] = $this->pages()->getByTag(ServicePages::FEEDBACK_SUCCESS);

        return $this->renderPage('@PostroykaApp/Pages/page.html.twig', $data);
    }

    /**
     * О нас
     */
    public function aboutType($data, Request $request)
    {
        return $this->renderPage('@PostroykaApp/Pages/about.html.twig', $data);
    }

    /**
     * Вакансии
     */
    public function vacancyType($data, Request $request)
    {
        $form = $this->createForm(VacancyForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->mailer()->sendForm($form, 'mail.subject.vacancy', $request);

                return $this->redirectToRoute('vacancy_success');
            } catch (\Exception $e) {
                $this->notifier()->error();
            }
        }

        $data['form'] = $form->createView();

        return $this->renderPage('@PostroykaApp/Pages/vacancy.html.twig', $data);
    }

    /**
     * Письмо по вакансии отправлена успешно
     */
    public function vacancySuccessAction()
    {
        $data['page'] = $this->pages()->getByTag(ServicePages::VACANCY_SUCCESS);

        return $this->renderPage('@PostroykaApp/Pages/page.html.twig', $data);
    }

    public function propositionType($data, Request $request)
    {
        $form = $this->createForm(DirectorForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->mailer()->sendForm($form, 'mail.subject.proposition', $request);

                return $this->redirectToRoute('director_success');
            } catch (\Exception $e) {
                $this->notifier()->error();
            }
        }

        $data['form'] = $form->createView();

        return $this->renderPage('@PostroykaApp/Pages/director.html.twig', $data);
    }

    /**
     * Директору
     */
    public function directorType($data, Request $request)
    {
        $form = $this->createForm(DirectorForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->mailer()->sendForm($form, 'mail.subject.director', $request);

                return $this->redirectToRoute('director_success');
            } catch (\Exception $e) {
                $this->notifier()->error();
            }
        }

        $data['form'] = $form->createView();
        $data['place'] = 'Фамилия Имя Отчество';

        return $this->renderPage('@PostroykaApp/Pages/director.html.twig', $data);
    }

    /**
     * Письмо директору отправлена успешно
     */
    public function directorSuccessAction()
    {
        $data['page'] = $this->pages()->getByTag(ServicePages::DIRECTOR_SUCCESS);

        return $this->renderPage('@PostroykaApp/Pages/page.html.twig', $data);
    }

    /**
     * Каталог
     */
    public function catalogSectionType($data, Request $request)
    {
        $data['groups'] = $this->getCatalogGroups();

        return $this->renderPage('@PostroykaApp/Pages/catalog_section.html.twig', $data);
    }

    /**
     * Группа товаров
     */
    public function catalogGroupType($data, Request $request)
    {
        // Хак для правильного отображения вложенности каталога
        $this->session()->set(self::REFERER_SESSION_KEY, $request->getUri());

        // Проверяем на подгруппы
        $filter = new PagesFilter([CatalogProvider::GROUP_TYPE, CatalogProvider::PAGE_TYPE]);
        $subgroups = $this->catalog()->getChildPages($data['page'], $filter);

        if ($subgroups) {
            foreach ($subgroups as $group) {
                $data['groups'][] = [
                    'group' => $group,
                ];
            }

            $popular = [];
            $filter = new PagesFilter([CatalogProvider::GROUP_TYPE]);
            $subgroups = $this->catalog()->getChildPages($data['page'], $filter);

            if ($subgroups) {
                $filter = new PagesFilter([CatalogProvider::PRODUCT_TYPE]);
                $filter->setField('show_in_section', true);
                $filter->setMaxResults(self::POPULAR_NUMBER);

                foreach ($subgroups as $subgroup) {
                    $products = $this->catalog()->getChildPages($subgroup, $filter);

                    foreach ($products as $product) {
                        $popular[] = $product;
                    }
                }
            }

            $data['popular'] = array_slice($popular, 0, self::POPULAR_NUMBER);

            return $this->renderPage('@PostroykaApp/Pages/catalog_main_group.html.twig', $data);
        }


        // Нет подгрупп - выводим товары
        $filter = new PagesFilter(CatalogProvider::PRODUCT_TYPE);
        $data['products'] = $this->catalog()->getChildPagesQuery($data['page'], $filter)->getResult();
        $data['properties'] = [[]];
        $filterProperties = [];


        // Фильтр
        /** @var Page $product */
        foreach ($data['products'] as $product) {
            $properties = $this->properties()->getEntityValuesQuery($product)->getResult();
            $data['properties'][$product->getId()] = $properties;

            if (!$filterProperties && $properties) {
                $filterProperties = $properties;
            }
        }

        $data['filter'] = array_slice($filterProperties, 0, 3);

        //отзывы
        $entityReviews = $this->reviews()->getEntitiesReviews($data['products']);
        $data['page_entity'] = $entityReviews;

        // SEO группы
        $filter = new PagesFilter(CatalogProvider::SEO_GROUP_TYPE);
        $data['seo_groups'] = $this->catalog()->getChildPagesQuery($data['page'], $filter)->getResult();

        // SEO группы 2
        $filter = new PagesFilter(CatalogProvider::SEO_GROUP_DOWN_TYPE);
        $data['seo_down_groups'] = $this->catalog()->getChildPagesQuery($data['page'], $filter)->getResult();
//        dd($data['seo_down_groups']);

        //таблица с минимальными ценами
        if (!empty($data['seo_groups'])){
            $data['cheap'] = $this->getMinPriceProduct($data['seo_groups']);
        }

        return $this->renderPage('@PostroykaApp/Pages/catalog_group.html.twig', $data);
    }


    /**
     * Получаем самые дешевые продукты из seo groups
     * @param $groups
     * @param $categoryTitle
     * @return mixed
     */
    public function getMinPriceProduct($groups)
    {
        $data = [];
        $result = [];

        foreach ($groups as $key => $group){
            $data[$key] = $group->getChild()->toArray();

            if (empty($data[$key])) continue;

            $test[$key] = $data[$key];
            foreach ($data[$key] as $index => $item){

                if (
                    is_null($item->getPage()->getUrl()) ||
                    $item->getPage()->isDeleted() ||
                    !$item->getPage()->isEnabled() ||
                    is_null($item->getPage()->getPrice()) ||
                    $item->getPage()->getPrice() == 0
                ) unset($data[$key][$index]);

            }

            if(count($data[$key]) > 1){
                usort($data[$key], function ($a, $b){
                    return  (float)$a->getPage()->getPrice() > (float)$b->getPage()->getPrice();
                });
            }

            $page = array_shift($data[$key]);
            if (is_null($page)) continue;

            try {
                if($page->getParent()->getTitle() == 'Гипсокартон с доставкой в Гатово'
                    || $page->getParent()->getTitle() == 'Гипсокартон с доставкой в Боровляны'
                    || $page->getParent()->getTitle() == 'Гипсокартон с доставкой в Колодищи'){
                    continue;
                }

            }catch (\Exception $exception){
                continue;
            }

            $result[$key]['name'] = $page->getParent()->getTitle();
            $result[$key]['price'] = $page->getPage()->getPrice();
            $result[$key]['link'] = $page->getParent()->getUrl();
        }

        return $result;
    }



    /**
     * Группа товаров для SEO
     */
    public function catalogSeoGroupType($data, Request $request)
    {
        $filter = new PagesFilter(CatalogProvider::PRODUCT_TYPE);
        $data['products'] = $this->catalog()->getChildPagesQuery($data['page'], $filter)->getResult();

        // SEO группы
        $filter = new PagesFilter(CatalogProvider::SEO_GROUP_TYPE);
        $parent = $data['page']->getParentPages()[0];
        $data['seo_groups'] = $this->catalog()->getChildPagesQuery($parent, $filter)->getResult();

        return $this->renderPage('@PostroykaApp/Pages/catalog_seo_group.html.twig', $data);
    }

    /**
     * Группа товаров для SEO 2
     */
    public function catalogSeoGroupDownType($data, Request $request)
    {
        $filter = new PagesFilter(CatalogProvider::PRODUCT_TYPE);
        $data['products'] = $this->catalog()->getChildPagesQuery($data['page'], $filter)->getResult();

        // SEO группы
        $filter = new PagesFilter(CatalogProvider::SEO_GROUP_DOWN_TYPE);
        $parent = $data['page']->getParentPages()[0];
        $data['seo_groups'] = $this->catalog()->getChildPagesQuery($parent, $filter)->getResult();

        return $this->renderPage('@PostroykaApp/Pages/catalog_seo_group.html.twig', $data);
    }



    /**
     * Продукт
     */
    public function productType($data, Request $request)
    {
        /** @var Page $product */
        $product = $data['page'];

        // Кастомные метатеги
        $metaTags = new MetaTagContainer();
        $metaTags->setTitle($product->getMetaTitle() ?: $product->getTitle() . ' — купить в Минске.');
        $metaTags->setDescription($product->getMetaDescription() ?: StringFormatExtension::shortStringFilter(
                $product->getDescription()) . ' — описание, отзывы, фото и цены. Доставка по Минску и Беларуси.'
        );
        $metaTags->setKeywords($product->getMetaKeywords());
        $this->setMetaTags($metaTags);

        // Форма отзыва
        $review = new Review($product);
        $form = $this->createForm(ReviewForm::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $this->reviewsManager()->create($review);
                    $this->notifier()->success('Ваш отзыв будет проверен и опубликован позже. Спасибо.');

                    return $this->redirect($this->convertUrl($product->getUrl()));
                } catch (\Exception $e) {
                    $this->notifier()->error();
                }
            } else {
                $this->notifier()->error('Форма заполнена с ошибками!');
            }
        }

        $data['form'] = $form->createView();
        $data['properties'] = $this->properties()->getEntityValuesQuery($product)->getResult();

        // Похожие товары
        $data['similar'] = $this->getLinkedProducts($product, self::SIMILAR_NUMBER);

        // Сопутствующие товары
        $group = $product->getParentPages()[0];
        $data['related'] = $group ? $this->getLinkedProducts($group, self::RELATED_NUMBER) : [];

        // Отзывы
        $reviews = $this->reviews()->getEntityReviewsQuery($product);
        $data['reviews'] = $this->paginator()->paginate($reviews, $request->get('page', 1), self::REVIEWS_PER_PAGE, ['wrap-queries' => true]);

        return $this->renderPage('@PostroykaApp/Pages/product.html.twig', $data);
    }

    /**
     * Товары на акции
     */
    public function stockType($data, Request $request)
    {
        $data['products'] = $this->catalog()->getStockAndMarkdownQuery()->getResult();
        $data['view'] = $request->get('view', 'tile');

        return $this->renderPage('@PostroykaApp/Pages/stock.html.twig', $data);
    }

    /**
     * Best sellers
     */

    public function salesLeaderType($data, Request $request)
    {
        $data['products'] = $this->catalog()->getSalesLeadersQuery()->getResult();
        $data['view'] = $request->get('view', 'tile');

        return $this->renderPage('@PostroykaApp/Pages/sales_leader.html.twig', $data);
    }

    /**
     * Новинки
     */
    public function noveltyType($data, Request $request)
    {
        $data['products'] = $this->catalog()->getNoveltyQuery()->getResult();
        $data['view'] = $request->get('view', 'tile');

        return $this->renderPage('@PostroykaApp/Pages/novelty.html.twig', $data);
    }

    /**
     * Новинки
     */
    public function sales_leaderType($data, Request $request)
    {
        $data['products'] = $this->catalog()->getSalesLeadersQuery()->getResult();
        $data['view'] = $request->get('view', 'tile');

        return $this->renderPage('@PostroykaApp/Pages/novelty.html.twig', $data);
    }

    /**
     * Поиск товаров
     */
    public function searchType($data, Request $request)
    {
        $search = trim($request->query->get('search', ''));
//        dd($request->query->all());
        if (mb_strlen($search) >= 2) {
            $filter = new PagesFilter([CatalogProvider::PRODUCT_TYPE, CatalogProvider::MANUFACTURER_FIELD]);
            $filter->addOrderBy('page.id', PagesFilter::ORDER_ASC);
            $fields = ['title', 'productId', 'metaKeywords'];
            $data['products'] = $this->catalog()->searchQuery($search, $fields, $filter, $request)->setMaxResults(99)->getResult();
            $data['filter'] = $filter;

            if (empty($data['products'])) {
                $event = new PageSearchQueryEvent((string)$search, $this->getUser());
                $this->dispatcher->dispatch($event, $event->getEventName());
            }
        } else {
            $data['products'] = [];
        }

        $data['search'] = $search;
        $data['view'] = $request->get('view', 'tile');
        $data['request_fields'] = $request->query->all();

        //adam
        $data['subgroups'] = $this->catalog()->getChildGroup();
        $brandPage = $this->pages()->getByTag('brands');
        $filter = new PagesFilter(self::MANUFACTURER_TYPE);
        $data['brands'] = $this->pages()->getChildPages($brandPage, $filter);

        return $this->renderPage('@PostroykaApp/Pages/search.html.twig', $data);
    }

    /**
     * Каталог брендов
     */
    public function manufacturerGroupType($data, Request $request)
    {
        $filter = new PagesFilter(self::MANUFACTURER_TYPE);
        $brands = $this->pages()->getChildPages($data['page'], $filter);

        foreach ($brands as $brand) {
            /** @var Page $brand */
            $firstLetter = mb_strtoupper(mb_substr($brand->getTitle(), 0, 1));
            $data['brands'][$firstLetter][] = $brand;
        }

        ksort($data['brands']);

        return $this->renderPage('@PostroykaApp/Pages/manufacturers.html.twig', $data);
    }

    /**
     * Определенный бренд
     */
    public function manufacturerType($data, Request $request)
    {
        // Просмотр страницы бренда
        $categoryId = $request->get('category', false);

        if (!$categoryId) {
            $data['groups'] = $this->catalog()->getGroupsProductsManufacturerQuery($data['page'])->getResult();
            return $this->renderPage('@PostroykaApp/Pages/manufacturer_groups.html.twig', $data);
        }

        // Просмотр товаров бренда в определенной категории
        $group = $this->pages()->get($categoryId);

        if (!$group) {
            throw $this->createNotFoundException();
        }

        /** @var Page $manufacturer */
        $manufacturer = $data['page'];

        $filter = new PagesFilter(CatalogProvider::PRODUCT_TYPE);
        $filter->setField(CatalogProvider::MANUFACTURER_FIELD, $manufacturer->getId());
        $data['products'] = $this->pages()->getChildPagesQuery($group, $filter)->getResult();

        $redirect = $group->getUrl() . mb_strtolower($data['page']->getTitle()) . '/';
        $pageToRedirect = $this->pages()->getByUrl($redirect);

        if (is_null($pageToRedirect->getId())) return $this->renderPage('@PostroykaApp/Pages/manufacturer_group_products.html.twig', $data);

        return $this->redirect($redirect);
    }

    /**
     * Получение связанных товаров
     * @param Page $page
     * @param int $number
     * @return Page[]
     */
    private function getLinkedProducts(Page $page, $number)
    {
        $linkedPages = $page->getLinkedPages();
        $result = [];

        /** @var Relations $link */
        foreach ($linkedPages as $link) {
            $linkedPage = $link->getPage();

            if ($linkedPage->isEnabled() && !$linkedPage->isDeleted()) {
                $result[] = $linkedPage;
            }
        }

        return array_slice($result, 0, $number, true);
    }
}
