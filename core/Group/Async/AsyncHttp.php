<?php

namespace Group\Async;

use Config;
use Group\Async\Client\Http;
use Group\Async\Client\Dns;

class AsyncHttp
{   
    protected $domain;

    protected $serv;

    protected $port;

    protected $ssl;

    protected $timeout = 3;

    protected $keepalive = false;

    protected $data = [];

    protected $cookies = [];

    protected $headers = [
        "User-Agent" => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Accept-Encoding' => 'gzip, deflate'
    ];

    public function __construct($domain)
    {   
        $this->domain = $domain;
    }

    /**
     * 设置超时时间
     * @param  int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
    
    public function setKeepalive($keepalive)
    {
        $this->keepalive = $keepalive;
    }

    public function setCookies(array $cookies)
    {
        $this->cookies = $cookies;
    }

    public function setHost($host)
    {
        $this->headers['Host'] = $host;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * 返回一个异步http客户端
     * @param  string $path
     * @return Group\Async\Client\Http
     */
    public function getClient($path)
    {   
        $client = new Http($this->serv, $this->port, $this->ssl);
        $client->setTimeout($this->timeout);
        $client->setKeepalive($this->keepalive);
        $client->setCookies($this->cookies);
        $client->setHeaders($this->headers);
        $client->setPath($path);
        return $client;
    }

    /**
     * get请求
     * @param  string $path
     * @param  array $data
     * @return array|false
     */
    public function get($path, $data = [])
    {   
        yield $this->parseDomain();

        if ($data) {
            $path .= "?".http_build_query($data);
        }
        $client = $this->getClient($path);
        $client->setMethod("GET");

        $res = (yield $client);
        if ($res && $res['response']) {
            yield $res['response'];
        }

        yield false;
    }

    /**
     * post请求
     * @param  string $path
     * @param  array $data
     * @return array|false
     */
    public function post($path, $data = [])
    {   
        yield $this->parseDomain();

        $this->headers['Content-Type'] = "application/x-www-form-urlencoded;charset=UTF-8";
        $client = $this->getClient($path);
        $client->setMethod("POST");
        $client->setData(http_build_query($data));

        $res = (yield $client);
        if ($res && $res['response']) {
            yield $res['response'];
        }

        yield false;
    }

    /**
     * 解析域名
     */
    public function parseDomain()
    {   
        preg_match("/^http:\/\/(.*)/", $this->domain, $matchs);
        if ($matchs) {
            $this->port = 80;
            yield $this->dnsLookup($matchs[1]);
            return;
        }

        preg_match("/^https:\/\/(.*)/", $this->domain, $matchs);
        if ($matchs) {
            $this->port = 443;
            $this->ssl = true;
            yield $this->dnsLookup($matchs[1]);
            return;
        }

        throw new \Exception("Error domain, must start with http:// or https://", 1);   
    }

    /**
     * 解析dns
     * @param  string $domain
     * @return [type]
     */
    private function dnsLookup($domain)
    {
        $domains = explode(":", $domain);
        if (count($domains) == 2) {
            $this->serv = $domains[0];
            $this->port = $domains[1];
            yield;
        }

        $dns = new Dns($domain);
        $res = (yield $dns);

        if ($res && $res['response']) {
            $this->serv = $res['response']['ip'];
            $this->setHost($res['response']['host']);
        }
    }
}
