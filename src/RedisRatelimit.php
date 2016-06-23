<?php

/*
 * Memcache Ratelimiting for Slim 3.x Framework
 *
 * Copyright (c) 2016 Nils Hulsch
 *
 * Licensed under the MIT license:
 *      http://www.opensource.org/licenses/mit-license.php
 *
 */

namespace Slim\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \RedisClient\RedisClient;


class RedisRatelimit
{

    /**
     * @var \RedisClient $redis
     */
    private $redis = null;

    private $maxRequests;
    private $expire;

    public function __construct($host = 'tcp://127.0.0.1:6379', $maxRequests = 100, $expire = 300)
    {
        $this->redis = new RedisClient([
            'server' => $host,
            'timeout' => 2
        ]);

        $this->maxRequests = $maxRequests;
        $this->expire = $expire;
    }

    public function __invoke(RequestInterface $requestInterface, ResponseInterface $responseInterface, $next)
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $reformattedSource = preg_replace("/[.:]/", "_", $_SERVER['HTTP_CF_CONNECTING_IP']);
        } else {
            $reformattedSource = preg_replace("/[.:]/", "_", $_SERVER['REMOTE_ADDR']);
        }

        $requestCount = count($this->redis->keys(sprintf("rl.%s.*", $reformattedSource)));
        if ($requestCount > $this->maxRequests) {
            $responseInterface = $responseInterface->withStatus(429);
            $responseInterface->getBody()->write("Rate limit exceeded.");
            return $responseInterface;
        } else {
            $key = sprintf("rl.%s.%s", $reformattedSource, microtime(true));
            $this->redis->set($key, 1);
            $this->redis->expire($key, $this->expire);
            $responseInterface = $next($requestInterface, $responseInterface);
        }

        return $responseInterface;
    }
}