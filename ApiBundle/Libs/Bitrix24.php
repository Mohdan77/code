<?php

namespace Postroyka\ApiBundle\Libs;

/**
 * Class Bitrix24
 * Библиотека для работы с api Bitrix24
 */
class Bitrix24
{
    const MAX_BATCH_CALLS = 50;
    private $_domain;
    private $_auth;
    private $_api_url;

    private $_batch = array();

    /**
     * Bitrix24 constructor.
     * @param array $params Массив настроек содержащий домен и авторизационный токен
     * @throws Exception
     */
    public function __construct($params = array())
    {
        if (empty($params) || !isset($params['domain'], $params['auth']))
            throw new Exception('Ivalid arguments');

        $this->_domain = $params['domain'];
        $this->_auth = $params['auth'];
        $this->_api_url = "{$this->_domain}/rest/1/{$this->_auth}";
    }

    /**
     * Получает ID контакта либо создает новый
     * @param $contact_data
     * @return mixed
     * @throws JsonException
     */
    public function getOrCreateContact($contact_data)
    {
        $contact = $this->_call(
            'crm.contact.list',
            [
                'filter' => [
                    'EMAIL' => $contact_data['fields']['EMAIL'][0]['VALUE'] ?: null,
                    'PHONE' => $contact_data['fields']['PHONE'][0]['VALUE'] ?: null,
                ]
            ]
        );

        if (isset($contact['result']) && !empty($contact['result'])) {
            $contact_id = $contact['result'][0]['ID'];
        } else {
            $new_contact = $this->_call('crm.contact.add', $contact_data);
            $contact_id = $new_contact['result'];
        }

        return $contact_id;
    }

    /**
     * Создает сделку
     * @param $fields
     * @return bool|mixed
     * @throws JsonException
     */
    public function addDeal($fields)
    {
        $new_deal = $this->_call('crm.deal.add', ['fields' => $fields]);
        return $new_deal['result'];
    }

    /**
     * Получает товарные позиции сделки
     * @param $deal_id
     * @return mixed
     * @throws JsonException
     */
    public function getDealProductsRow($deal_id)
    {
        $products_row = $this->_call('crm.deal.productrows.get', ['ID' => $deal_id]);

        if (isset($products_row['error']) || !$products_row['result'])
            throw new Exception('Ошибка в запросе crm.deal.productrows.get. Результат ответа: ' . var_export($products_row, true));

        return $products_row['result'];
    }

    /**
     * Прикрепляет товарные позиции к сделке
     * @param $deal_id
     * @param $products_list
     * @return bool
     * @throws JsonException
     */
    public function addDealProductsRow($deal_id, $products_list)
    {
        $result = $this->_call('crm.deal.productrows.set', ['ID' => $deal_id, 'ROWS' => $products_list]);

        return isset($result['error']);
    }

    /**
     * Получает седлку по ее идентификатору
     * @param $deal_id
     * @return mixed
     * @throws JsonException
     */
    public function getCRMDeal($deal_id)
    {
        $deal = $this->_call('crm.deal.get', ['ID' => $deal_id]);

        if (isset($deal['error']) || !$deal['result'])
            throw new Exception('Ошибка в запросе crm.deal.get. Результат ответа: ' . var_export($deal, true));

        return $deal['result'];
    }

    /**
     * Получает сделки
     * @param array $filter
     * @param array $select
     * @return array|mixed
     * @throws JsonException
     */
    public function getDeals($filter = array(), $select = array())
    {
        $deal = $this->_call('crm.deal.list', ['filter' => $filter, 'select' => $select]);

        $result_deals = $deal['result'] ?? array();

        if (isset($deal['next'])) {

            for ($start = $deal['next']; $start <= $deal['total']; $start += 50) {
                $this->_add_batch('crm.deal.list', ['start' => $start, 'filter' => $filter, 'select' => $select]);
            }
        }

        $batch_deals = $this->_call_batch();

        if (isset($batch_deals[0]['result']['result'])) {
            foreach ($batch_deals[0]['result']['result'] as $batch_result_item) {
                $result_deals = array_merge($result_deals, $batch_result_item);
            }
        }

        if (isset($deal['error']) || !$deal['result'])
            throw new Exception('Ошибка в запросе crm.deal.get. Результат ответа: ' . var_export($deal, true));

        return $result_deals;
    }

    /**
     * Получает товарные позиции сделки
     * @param array $deals_ids
     * @return array
     */
    public function getDealsProductRows($deals_ids = array())
    {

        foreach ($deals_ids as $deals_id) {
            $this->_add_batch('crm.deal.productrows.get', ['ID' => $deals_id]);
        }

        $batch_productsrows = $this->_call_batch();

        $result_productsrows = array();
        foreach ($batch_productsrows as $batch_productsrow) {
            $result_productsrows = array_merge($result_productsrows, $batch_productsrow['result']['result']);
        }

        $result = array();

        foreach ($result_productsrows as $result_productsrow) {
            foreach ($result_productsrow as $item) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Возвращает список товаров, либо пустой массив в случае неудачного запроса.
     * @param array $filter
     * @param array $select
     * @return array
     * @throws JsonException
     */
    public function getProductsList($filter = array(), $select = array())
    {

        $data = array(
            'filter' => $filter,
            'select' => $select
        );
        $products = $this->_call(
            'crm.product.list',
            $data
        );

        $result_products = $products['result'] ?? array();

        if (isset($products['next'])) {

            for ($start = $products['next']; $start <= $products['total']; $start += 50) {
                $data['start'] = $start;
                $this->_add_batch('crm.product.list', $data);
            }
        }

        $batch_products = $this->_call_batch();

        if (isset($batch_products[0]['result']['result'])) {
            foreach ($batch_products[0]['result']['result'] as $batch_result_item) {
                $result_products = array_merge($result_products, $batch_result_item);
            }
        }

        return $result_products;
    }

    /**
     * Возвращает товар
     * @param array $filter
     * @param array $select
     * @return mixed|null
     * @throws JsonException
     */
    public function getProduct($filter = array(), $select = array())
    {
        $data = array(
            'filter' => $filter,
            'select' => $select
        );

        $result = $this->_call('crm.product.list', $data);
        usleep(5000);
        return isset($result['result']) && !empty($result['result']) ? $result['result'] : null;
    }

    private function _call($method, $data)
    {
        $queryUrl = "{$this->_api_url}/{$method}/";
        $queryData = http_build_query($data);
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData
            )
        );
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result, true, 512);
    }


    public function _call_batch($halt = 0)
    {
        $batchResult = array();
        while (count($this->_batch)) {
            $slice = array_splice($this->_batch, 0, self::MAX_BATCH_CALLS);

            $commands = array();
            foreach ($slice as $idx => $call) {
                $commands[$idx] = $call['method'] . '?' . http_build_query($call['parameters']);
            }

            $batchResult[] = $this->_call('batch', array('halt' => $halt, 'cmd' => $commands));

            sleep(1);
        }

        return $batchResult;
    }

    private function _add_batch($method, array $parameters = array())
    {
        $id = uniqid();
        $this->_batch[$id] = array(
            'method' => $method,
            'parameters' => $parameters,
        );

        return $id;
    }
}