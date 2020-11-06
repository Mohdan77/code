<?php

namespace Postroyka\AppBundle\Controller;

use Knp\Snappy\Pdf;
use Postroyka\AppBundle\Provider\CartProvider;
use Postroyka\AppBundle\Service\OrderCalculator;
use Submarine\CoreBundle\Options\OptionsCacheProvider;
use Submarine\PagesBundle\Filter\PagesFilter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends AbstractController
{
    const SITEMAP_CACHE_ID = 'sitemap';
    const SITEMAP_CACHE_TIME = 60 * 60 * 12;
    const ROBOTS_BLOCK_ID = 'robots';
    const PRICE_PATH = '/media/price.pdf';
    const PRICE_DATE = 'price_date';

    /**
     * @return Pdf
     */
    private function pdfGenerator()
    {
        return $this->get('knp_snappy.pdf');
    }

    /**
     * @return OptionsCacheProvider
     */
    private function options()
    {
        return $this->get('core.options_cache');
    }

    /**
     * Карта сайта
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function sitemapAction()
    {
        if (false === ($sitemap = $this->cache()->fetch(self::SITEMAP_CACHE_ID))) {
            $filter = new PagesFilter([
                'product',
                'page',
                'contacts',
                'about',
                'catalogGroup',
                'stock',
                'catalogSection',
                'director',
                'catalogSeoGroup',
                'article',
                'articlesGroup',
                'articlesSection',
                'novelty',
                'sales-leader',
            ]);

            $home = $this->pages()->getByUrl('/');
            $locations = $this->pages()->filterQuery($filter)->getArrayResult();

            $data = [
                'home' => $home,
                'locations' => $locations,
            ];

            $sitemap = $this->renderView('@PostroykaApp/Service/sitemap.xml.twig', $data);

            $this->cache()->save(self::SITEMAP_CACHE_ID, $sitemap, self::SITEMAP_CACHE_TIME);
        }

        return new Response($sitemap, 200, ['Content-type' => 'text/xml']);
    }

    /**
     * Robots.txt
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function robotsAction()
    {
        $robots = $this->get('submarine.blocks.blocks_provider')->get(self::ROBOTS_BLOCK_ID)->getValue();

        return new Response($robots, 200, ['Content-type' => 'text/plain']);
    }

    /**
     * Генерация YML (Yandex Market Language)
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function ymlAction()
    {
        $data = [
            'root' => $this->catalog()->getByTag('catalog'),
            'categories' => $this->catalog()->getGroupsQuery()->getResult(),
            'products' => $this->catalog()->getActiveProductsQuery()->getResult()
        ];

        $yml = $this->renderView('@PostroykaApp/Service/yml.xml.twig', $data);

        return new Response($yml, 200, ['Content-type' => 'text/xml']);
    }

    /**
     * Прайс
     *
     * @return BinaryFileResponse|Response
     *
     * @throws \Exception
     */
    public function priceAction()
    {
        $priceUpdatedAt = new \DateTime($this->options()->getValue(CartProvider::PRICE_UPDATED_AT));
        $priceDate = $this->cache()->fetch(self::PRICE_DATE) ?: new \DateTime('01.01.2000');
        $now = new \DateTime();
        $webDir = $this->container->getParameter('kernel.web_dir');

        if (!file_exists($webDir . self::PRICE_PATH) || $priceDate < $priceUpdatedAt || $priceDate->format('d') !== $now->format('d')) {
            $data = [
                'price' => $this->catalog()->getGroupedProducts(),
                'silver_card' => $this->options()->getValue(OrderCalculator::SILVER_CARD),
                'gold_card' => $this->options()->getValue(OrderCalculator::GOLD_CARD),
                'fixed_discount' => OrderCalculator::FIXED_DISCOUNT,
            ];

            $view = $this->renderView('@PostroykaApp/Service/price.html.twig', $data);

            try {
                $this->pdfGenerator()->generateFromHtml($view, $webDir . self::PRICE_PATH, [], true);
            } catch (\Exception $e) {
                return new Response($view);
            }

            $this->cache()->save(self::PRICE_DATE, $now);
        }

        return new BinaryFileResponse($webDir . self::PRICE_PATH);
    }

    /**
     * Генерация фида для адаптивных объявлений Google
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function googleFeedAction()
    {
        $data = [
            'products' => $this->catalog()->getActiveProductsQuery()->getResult()
        ];

        $yml = $this->renderView('@PostroykaApp/Service/google_feed.tsv.twig', $data);

        return new Response($yml, 200, ['Content-type' => 'text/tab-separated-values']);
    }

    /**
     * Генерация фида для My Target
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function myTargetFeedAction()
    {
        $data = [
            'root' => $this->catalog()->getByTag('catalog'),
            'categories' => $this->catalog()->getGroupsQuery()->getResult(),
            'products' => $this->catalog()->getActiveProductsQuery()->getResult()
        ];

        $yml = $this->renderView('@PostroykaApp/Service/my_target_feed.xml.twig', $data);

        return new Response($yml, 200, ['Content-type' => 'text/xml']);
    }

    /**
     * Генерация фида для Facebook
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function facebookFeedAction()
    {
        $data = [
            'products' => $this->catalog()->getActiveProductsQuery()->getResult()
        ];

        $yml = $this->renderView('@PostroykaApp/Service/facebook_feed.xml.twig', $data);

        return new Response($yml, 200, ['Content-type' => 'text/xml']);
    }
}
