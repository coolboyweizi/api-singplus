<?php

namespace SingPlus\Support\Google;

use Google_Client;
use Google_Service_AndroidPublisher;
use Symfony\Component\Cache\Adapter\AbstractAdapter as CacheAdapter;

/**
 * ApiServiceAdapter just a adapter for google service api calling
 * only client init be handled
 * detail service docs please to see docs of specified services
 * Usage:
 *      $apiAdapter = new ApiServiceAdapter()
 *      $service = $apiAdapter->service('AndroidPublisher');
 *      $service->purchases_products                        // detail docs please to see Google_Service_AndroidPublisher
 *              ->get($packageName, $productId, $token)
 */
class ApiServiceAdapter
{
    static $servicesMapping = [
        Google_Service_AndroidPublisher::class  => Google_Service_AndroidPublisher::ANDROIDPUBLISHER,
    ];

    private $client;

    public function __construct(CacheAdapter $cache)
    {
        $this->client = new Google_Client();
        $this->client->useApplicationDefaultCredentials();
        $this->client->setCache($cache);
    }

    /**
     * @param string $serviceName   google service name, without Google_Service prefix
     */
    public function service(string $serviceName)
    {
        static $services = [];
        $serviceName = sprintf('Google_Service_%s', $serviceName);
        if ( ! isset(self::$servicesMapping[$serviceName])) {
            throw \Exception(sprintf('service: %s not invalid', $serviceName));
        }

        $service = array_get($services, $serviceName);
        if ( ! $service) {
            $this->client->addScope(self::$servicesMapping[$serviceName]);
            $service = new $serviceName($this->client);
            $services[$serviceName] = $service;
        }

        return $service;
    }
}
