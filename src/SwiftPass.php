<?php

namespace Barbery;

use GuzzleHttp\Client;
use SimpleXMLElement;

/**
 * 威富通支付工具类
 */
class SwiftPass
{
    private $config = [];

    const HTTP_TIMEOUT = 6.0;
    const GATEWAY      = 'https://pay.swiftpass.cn/pay/gateway';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function pay(array $data)
    {
        $data['service'] = 'pay.weixin.jspay';
        $data            = array_merge($data, [
            'mch_create_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1',
            'notify_url'    => $this->config['notify_url'],
        ]);

        return $this->_post($data);
    }

    public function getPayLink($tokenId, $showWxTitle = 1)
    {
        return "https://pay.swiftpass.cn/pay/jspay?token_id={$tokenId}&showwxtitle={$showWxTitle}";
    }

    public function refund(array $data)
    {
        $data['service'] = 'unified.trade.refund';
        $data            = array_merge($data, ['op_user_id' => $this->config['mch_id']]);

        return $this->_post($data);
    }

    public function getTradeDetail(array $data)
    {
        $data['service'] = 'unified.trade.query';

        return $this->_post($data);
    }

    public function getRefundDetail(array $data)
    {
        $data['service'] = 'unified.trade.refundquery';

        return $this->_post($data);
    }

    public function isValidSign($sign, $data)
    {
        return $sign === $this->_getSign($data);
    }

    private function _getNonceStr()
    {
        return md5(random_bytes(16));
    }

    private function _getSign($data)
    {
        if (is_array($data)) {
            ksort($data);
        }

        $str = '';
        foreach ($data as $key => $value) {
            $str .= "{$key}={$value}&";
        }

        $str .= "key={$this->config['mch_key']}";

        return strtoupper(md5($str));
    }

    private function _post(array $data)
    {
        $Client = new Client([
            'timeout' => self::HTTP_TIMEOUT,
        ]);
        $Response = $Client->request('POST', self::GATEWAY, ['body' => $this->_prepare($data)]);

        return simplexml_load_string($Response->getBody());
    }

    private function _prepare(array $data)
    {
        $data['mch_id']    = $this->config['mch_id'];
        $data['nonce_str'] = $this->_getNonceStr();
        $data['sign']      = $this->_getSign($data);

        return $this->_arrayToXML($data);
    }

    private function _arrayToXML($data)
    {
        $xml = new SimpleXMLElement('<xml/>');
        foreach ($data as $key => $value) {
            $xml->addChild($key, $value);
        }

        return $xml->asXML();
    }
}
