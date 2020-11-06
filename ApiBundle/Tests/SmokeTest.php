<?php

namespace Postroyka\ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function successGetUrls()
    {
        return [
            ['/api/secret/export_users'],
            ['/api/secret/export_groups'],
            ['/api/secret/export_products'],
            ['/api/secret/export_orders'],
        ];
    }

    public function successPostUrls()
    {
        return [
            ['/api/secret/import_price'],
        ];
    }

    /**
     * @dataProvider successGetUrls
     */
    public function testGetUrlIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @dataProvider successPostUrls
     */
    public function testPostUrlIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request('POST', $url, ['price' => json_encode([0 => 0])]);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}