<?php

namespace Ycstar\Douyin;

use Ycstar\Douyin\Exceptions\InvalidPublicKeyException;

class Sign
{
    protected $platformPublicKey;

    public function setPublicKey(string $keyStr)
    {
        $this->platformPublicKey = $keyStr;
    }

    public function verifySignature($http_body, $timestamp, $nonce_str, $sign)
    {
        $data = $timestamp . "\n" . $nonce_str . "\n" . $http_body . "\n";
        if (!$this->platformPublicKey) {
            throw new InvalidPublicKeyException(" verify signature wihtout public key");
        }
        $res = openssl_get_publickey($this->platformPublicKey); // 注意验签时publicKey使用平台公钥而非应用公钥
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        return $result;  //bool
    }
}