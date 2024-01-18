<?php

namespace Ycstar\Douyin;

use GuzzleHttp\Client;
use Ycstar\Douyin\Exceptions\InvalidArgumentException;
use Ycstar\Douyin\Exceptions\InvalidResponseException;

class Douyin
{
    protected $host;

    protected $key;

    protected $secret;

    protected $platformPublicKey;

    protected $accessToken = '';

    protected $expiresIn = 7200;

    protected $client;

    protected $currentMethod = [];

    protected $isTry = false;

    protected $isCache = true;

    public function __construct(array $config)
    {
        if (!isset($config['host'])) {
            throw new InvalidArgumentException("Missing Config -- [host]");
        }

        if (!isset($config['key'])) {
            throw new InvalidArgumentException("Missing Config -- [key]");
        }

        if (!isset($config['secret'])) {
            throw new InvalidArgumentException("Missing Config -- [secret]");
        }

        $this->host = $config['host'];
        $this->key = $config['key'];
        $this->secret = $config['secret'];
        $this->platformPublicKey = $config['platform_public_key'] ?? '';

        if(isset($config['is_cache'])){
            $this->isCache = $config['is_cache'];
        }
    }

    /**
     * 获取AccessToken
     * @return mixed|string|null
     * @throws Exceptions\LocalCacheException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccessToken()
    {
        if(!empty($this->accessToken)){
            return ['access_token' => $this->accessToken, 'expires_in' => $this->expiresIn];
        }
        if($this->isCache){
            $cache = $this->key . '_access_token';
            $cacheInfo = Tools::getCache($cache);
            if (!empty($cacheInfo)) {
                $this->accessToken = $cacheInfo[0] ?? '';
                $this->expiresIn = $cacheInfo[1] ?? 7200;
                return ['access_token' => $this->accessToken, 'expires_in' => $this->expiresIn];
            }
        }

        $response = $this->getHttpClient()->post('/oauth/client_token/', [
            'json' => [
                'client_key' => $this->key,
                'client_secret' => $this->secret,
                'grant_type' => 'client_credential'
            ],
        ])->getBody()->getContents();
        $result = json_decode($response, true);
        $data = $result['data'];
        if ($data['error_code'] != 0){
            throw new InvalidResponseException($data['description'], $data['error_code']);
        }

        $this->accessToken = $data['access_token'];
        $this->expiresIn = $data['expires_in'];
        if($this->isCache && !empty($this->accessToken)){
            Tools::setCache($cache, $this->accessToken, $this->expiresIn);
        }
        return ['access_token' => $this->accessToken, 'expires_in' => $this->expiresIn];
    }

    /**
     * 设置外部接口 AccessToken
     * @param string $accessToken
     * 当用户使用自己的缓存驱动时，直接实例化对象后可直接设置 AccessToken
     * - 多用于分布式项目时保持 AccessToken 统一
     * - 使用此方法后就由用户来保证传入的 AccessToken 为有效 AccessToken
     */
    public function setAccessToken(string $accessToken, int $expiresIn = 7200)
    {
        if (!is_string($accessToken)) {
            throw new InvalidArgumentException("Invalid AccessToken type, need string.");
        }
        $this->accessToken = $accessToken;
        $this->expiresIn = $expiresIn;
        if($this->isCache){
            $cache = $this->key . '_access_token';
            Tools::setCache($cache, $accessToken, $expiresIn);
        }

    }

    public function delAccessToken()
    {
        $this->accessToken = '';
        $this->expiresIn = 7200;
        if($this->isCache){
            Tools::delCache($this->key . '_access_token');
        }
    }

    /**
     * 查询商品品类
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGoodsCategory(array $params = [])
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $options = [];
        if(empty($params)){
            $options = ['query' => $params];
        }
        $result = $this->doRequest('get', '/goodlife/v1/goods/category/get/', $options);
        return $result;
    }

    /**
     * 创建/更新商品
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function saveGoodsProduct(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('post', '/goodlife/v1/goods/product/save/', ['json' => $params]);
        return $result;
    }

    /**
     * 免审修改商品
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function freeAuditGoodsProduct(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('post', '/goodlife/v1/goods/product/free_audit/', ['json' => $params]);
        return $result;
    }

    /**
     * 创建/更新多SKU商品的SKU列表
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function batchSaveGoodsSku(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('post', '/goodlife/v1/goods/sku/batch_save/', ['json' => $params]);
        return $result;
    }

    /**
     * 上下架商品
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function operateGoodsProduct(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('post', '/goodlife/v1/goods/product/operate/', ['json' => $params]);
        return $result;
    }

    /**
     * 同步库存
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function syncGoodsStock(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('post', '/goodlife/v1/goods/stock/sync/', ['json' => $params]);
        return $result;
    }

    /**
     * 查询商品模板
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGoodsTemplate(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/goodlife/v1/goods/template/get/', ['query' => $params]);
        return $result;

    }

    /**
     * 查询商品草稿数据
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGoodsProductDraft(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/goodlife/v1/goods/product/draft/get/', ['query' => $params]);
        return $result;
    }

    /**
     * 查询商品草稿数据列表
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryGoodsProductDraft(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/goodlife/v1/goods/product/draft/query/', ['query' => $params]);
        return $result;
    }

    /**
     * 查询商品线上数据
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGoodsProductOnline(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/goodlife/v1/goods/product/online/get/', ['query' => $params]);
        return $result;
    }

    /**
     * 查询商品线上数据
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryGoodsProductOnline(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/goodlife/v1/goods/product/online/query/', ['query' => $params]);
        return $result;
    }

    /**
     * 批量查询sku
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGoodsSku(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/goodlife/v1/goods/sku/get/', ['query' => $params]);
        return $result;
    }

    /**
     *
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function postSkuOrder(array $params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/apps/trade/v2/order/create_order', ['json' => $params]);
        return $result;
    }

    public function refundSkuOrder($params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/apps/trade/v2/refund/create_refund', ['json' => $params]);
        return $result;
    }

    public function syncRefundResut($params)
    {
        $this->setCurrentMethod(__FUNCTION__, func_get_args());
        $result = $this->doRequest('get', '/apps/trade/v2/refund/merchant_audit_callback', ['json' => $params]);
        return $result;
    }

    private function setCurrentMethod($method, $arguments = [])
    {
        $this->currentMethod = ['method' => $method, 'arguments' => $arguments];
    }

    private function getHttpClient()
    {
        if(!$this->client){
            return new Client(['base_uri' => $this->host]);
        }
        return $this->client;
    }

    private function doRequest(string $method, $uri = '', array $options = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            $options['headers'] = [
                'access-token' => $accessToken['access_token'] ?? ''
            ];
            $response = $this->getHttpClient()->request($method, $uri, $options)->getBody()->getContents();
            $result = json_decode($response, true);

            if(!$result){
                throw new InvalidResponseException('invalid response');
            }
            $data = $result['data'];
            if($data['error_code'] != 0){
                $errorDescription = $data['description'];
                if(isset($data['extra'])){
                    $errorDescription = $data['description'] . ' ' . $data['sub_description'];
                }
                throw new InvalidResponseException($errorDescription, $data['error_code']);
            }
            return $result;

        } catch (InvalidResponseException $e){
            if (!$this->isTry && in_array($e->getCode(), [2190002, 2190008])) {
                $this->delAccessToken();
                $this->isTry = true;
                return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
            }
            throw new InvalidResponseException($e->getMessage(), $e->getCode());
        }
    }

    public function verify($http_body, $timestamp, $nonce_str, $signStr)
    {
        $sign = new Sign();
        $sign->setPublicKey($this->platformPublicKey);

        return $sign->verifySignature($http_body, $timestamp, $nonce_str, $signStr);
    }
}