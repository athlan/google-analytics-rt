<?php

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
 * @return int
 */
$getRealTimeActiveUsers = function (Google_Service_Analytics $service, $profileId) {
    /* @var $result Google_Service_Analytics_RealtimeData */
    $result = $service->data_realtime->get(
        'ga:' . $profileId,
        'rt:activeUsers'
    );

    $totals = $result->getTotalsForAllResults();

    return (int) $totals['rt:activeUsers'];
};

$profileId = filter_input(INPUT_GET, "profileId", FILTER_SANITIZE_NUMBER_INT);

$service = $googleServiceAnalyticsFactory($googleClientFactory);

echo $getRealTimeActiveUsers($service, $profileId);
