<?php


namespace App\Postroyka\ApiBundle\Libs;


use Submarine\PagesBundle\Manager\PagesManager;
use Symfony\Component\HttpClient\HttpClient;

class Onliner
{

    private $clientId = '4e4f5245b4337f1e4f27';

    private $clientSecret = 'd463eb90aa7fb73979bf47099bf1a9b140801198';

    private $connect = [];

    private $manager;

    public function __construct(PagesManager $manager)
    {
        $this->manager = $manager;
        $this->auth();
    }

    public function auth()
    {
        $process = curl_init("https://b2bapi.onliner.by/oauth/token");
       curl_setopt($process, CURLOPT_HTTPHEADER, ['Accept: application/json']);
       curl_setopt($process, CURLOPT_USERPWD, "4e4f5245b4337f1e4f27:d463eb90aa7fb73979bf47099bf1a9b140801198");
       curl_setopt($process, CURLOPT_POST, 1);
       curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
       curl_setopt($process, CURLOPT_POSTFIELDS, array('grant_type' => 'client_credentials'));

       $result = json_decode(curl_exec($process), true);

       curl_close($process);

       $this->connect = $result;
    }

    public function get($address)
    {
        $process = curl_init("https://b2bapi.onliner.by/$address?access_token={$this->connect['access_token']}");
        curl_setopt($process, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($process);
        curl_close($process);

        return json_decode($result, true);
    }


    public function updatePosition()
    {
        $positions = $this->get('positions');
        $data = [];


        foreach ($positions as $key => $item){
            $data[$key] = $item;

            $data[$key]['price'] = $this->manager->get($item['id'])->getPrice();


        }

        $res = $this->manager->searchPagesQuery($positions[0]['model'])->getResult();

        $process = curl_init("https://b2bapi.onliner.by/pricelists");

        curl_setopt($process,
            CURLOPT_HTTPHEADER,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                "Authorization: Bearer {$this->connect['access_token']}"
            ]
        );
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($process);
        curl_close($process);


        return $result;
    }




}