<?php

use Stash\Interfaces\PoolInterface;
use Stash\Invalidation;

require_once 'vendor/autoload.php';
$config = require 'config/config.php';

/**
 * @return Google_Client
 */
$googleClientFactory = function() use ($config) {
    $client = new Google_Client();
    $client->setAuthConfig($config["authConfig"]);

    if (array_key_exists("cacert", $config)) {
        // fix of the unknown certs
        // https://github.com/google/google-api-php-client/issues/1011
        $client->setHttpClient(new \GuzzleHttp\Client(array(
            'verify' => $config["cacert"],
        )));
    }

    return $client;
};

/**
 * @param $clientFactory
 * @return Google_Service_Analytics
 */
$googleServiceAnalyticsFactory = function($clientFactory) {
    $client = $clientFactory();
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
    return new Google_Service_Analytics($client);
};

/**
 * @param Google_Service_Analytics $service
 * @param $profileId
 * @return array
 */
$getRealTimeActiveUsers = function (Google_Service_Analytics $service, $profileId) {
    /* @var $result Google_Service_Analytics_RealtimeData */
    $result = $service->data_realtime->get(
        'ga:' . $profileId,
        'rt:activeUsers'
    );

    $totals = $result->getTotalsForAllResults();

    return [
        'time' => time(),
        'result' => (int) $totals['rt:activeUsers'],
    ];
};


/**
 * @param string $cacheManagerName
 * @return PoolInterface
 */
$cacheFactory = function ($cacheManagerName = "default") use ($config) {
    $cacheManagerConfig = $config['cache_pool'][$cacheManagerName];
    $driverName = $cacheManagerConfig['driver'];
    $driverOptions = $cacheManagerConfig['options'];
    $driver = new $driverName($driverOptions);
    return new Stash\Pool($driver);
};

/**
 * @param callable $callback
 * @param PoolInterface $cache
 * @param $cacheKey
 * @param $ttl
 *
 * @return mixed
 */
$runCached = function (callable $callback, PoolInterface $cache, $cacheKey, $ttl) {
    $item = $cache->getItem($cacheKey);
    $item->setInvalidationMethod(Invalidation::OLD);

    if (!$item->isHit()) {
        $item->lock();

        $result = $callback();

        $item->setTTL($ttl);
        $item->set($result);

        $cache->save($item);
    }
    else {
        $result = $item->get();
    }

    return $result;
};

$profileId = filter_input(INPUT_GET, "profileId", FILTER_SANITIZE_NUMBER_INT);

$getRealTimeActiveUsersCached = function ($profileId) use ($runCached, $getRealTimeActiveUsers, $googleClientFactory, $googleServiceAnalyticsFactory, $cacheFactory, $config) {
    $cache = $cacheFactory();
    $cacheKey = "ga-rt/activeUsers/" . $profileId;
    $ttl = $config['cache']['ttl'];

    return $runCached(function() use ($profileId, $getRealTimeActiveUsers, $googleClientFactory, $googleServiceAnalyticsFactory) {
        $service = $googleServiceAnalyticsFactory($googleClientFactory);
        return $getRealTimeActiveUsers($service, $profileId);
    }, $cache, $cacheKey, $ttl);
};

$result = $getRealTimeActiveUsersCached($profileId);

header("Last-Modified: " . date("r", $result['time']));
echo $result['result'];
