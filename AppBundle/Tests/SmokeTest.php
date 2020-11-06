<?php

namespace Postroyka\AppBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function successUrls()
    {
        return [
            ['/'],
            ['/catalog/'],
            ['/dostavka/'],
            ['/stock/'],
            ['/sales-leader/'],
            ['/about/'],
            ['/articles/'],
            ['/contacts/'],
            ['/feedback_success/'],
            ['/director/'],
            ['/director_success/'],
            ['/search/'],
            ['/price'],
            ['/sitemap.xml'],
            ['/yml.xml'],
            ['/google_feed.tsv'],
            ['/my_target_feed.xml'],
            ['/facebook_feed.xml'],
            ['/robots.txt'],
            ['/cart'],
            ['/order/print'],
            ['/js-api/search-catalog?term=search'],
            ['/js-api/calculate-unloading']
        ];
    }

    public function redirectUrls()
    {
        return [
            ['/order'],
        ];
    }

    /**
     * @dataProvider successUrls
     */
    public function testUrlIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider redirectUrls
     */
    public function testUrlIsRedirection($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isRedirection());
    }

}
