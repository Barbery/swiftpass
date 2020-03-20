<?php

namespace Barbery;

use Exception;
use GuzzleHttp\Client;
use SimpleXMLElement;

/**
 * 威富通支付工具类
 */
class SwiftPass
{
    private $config = [];

    const HTTP_TIMEOUT = 6.0;
    // const GATEWAY       = 'https://pay.swiftpass.cn/pay/gateway';
    const GATEWAY       = 'https://payapi.citicbank.com/pay/gateway';
    const SIGN_TYPE_RSA = 'RSA_1_256';

    public function __construct(array $config)
    {
        if (empty($config['sign_type']) && empty($config['mch_key'])) {
            throw new Exception('sign_type为md5加密方式时，mch_key不能为空');
        }

        if ($config['sign_type'] === self::SIGN_TYPE_RSA && empty($config['private_key']) && empty($config['platform_public_key'])) {
            throw new Exception('sign_type为RSA_1_256加密方式时，private_key、platform_public_key不能为空');
        }

        if (empty($config['notify_url'])) {
            throw new Exception('notify_url不能为空');
        }

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
        return "https://payapi.citicbank.com/pay/jspay?token_id={$tokenId}&showwxtitle={$showWxTitle}";
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
        if (isset($data['sign_type']) && $data['sign_type'] === self::SIGN_TYPE_RSA) {
            return openssl_verify($this->_getRSASign($data), base64_decode($sign), $this->config['platform_public_key'], OPENSSL_ALGO_SHA256) === 1;
        } else {
            return $sign === $this->_getMD5Sign($data);
        }
    }

    private function _getNonceStr()
    {
        return md5(random_bytes(16));
    }

    private function _getSignStr($data)
    {
        if (is_array($data)) {
            ksort($data);
        }

        // sign不参与签名
        unset($data['sign']);
        $str = '';
        foreach ($data as $key => $value) {
            $str .= "{$key}={$value}&";
        }

        return $str;
    }

    private function _getMD5Sign($data)
    {
        $str = $this->_getSignStr($data) . "key={$this->config['mch_key']}";
        return strtoupper(md5($str));
    }

    private function _getRSASign($data)
    {
        $str = rtrim($this->_getSignStr($data), '&');
        openssl_sign($str, $signature, $this->config['private_key'], OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    private function _post(array $data)
    {
        $Client = new Client([
            'timeout' => self::HTTP_TIMEOUT,
        ]);
        $Response = $Client->request('POST', self::GATEWAY, ['body' => $this->_prepare($data)]);
        return simplexml_load_string($Response->getBody()->getContents());
    }

    private function _prepare(array $data)
    {
        $data['mch_id']    = $this->config['mch_id'];
        $data['nonce_str'] = $this->_getNonceStr();
        if (isset($this->config['sign_type']) && $this->config['sign_type'] === self::SIGN_TYPE_RSA) {
            $data['sign_type'] = self::SIGN_TYPE_RSA;
            $data['sign']      = $this->_getRSASign($data);
        } else {
            $data['sign'] = $this->_getMD5Sign($data);
        }

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
