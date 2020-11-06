<?php

namespace Postroyka\AccountBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    protected $testUser = [
        '_email' => 'support@itmedia.by',
        '_password' => 'itmedia',
    ];

    public function successUrls()
    {
        return [
            ['/login'],
            ['/amnesia'],
            ['/registration'],
        ];
    }

    public function successAuthUrls()
    {
        return [
            ['/account'],
            ['/account/orders'],
            ['/account/order/1'],
            ['/account/profile'],
            ['/account/password'],
            ['/account/email'],
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
     * @dataProvider successAuthUrls
     */
    public function testAuthUrlIsSuccessful($url)
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form();
        $client->submit($form, $this->testUser);

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
