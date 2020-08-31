<?php

namespace MollieShopware\Components;

require_once __DIR__ . '/../Client/vendor/autoload.php';

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use MollieShopware\MollieShopware;

class MollieApiFactory
{
    /**
     * @var Config
     */
    protected $config;


    /**
     * MollieApiFactory constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }


    /**
     * @param null $shopId
     * @return MollieApiClient
     * @throws ApiException
     */
    public function create($shopId = null)
    {
        $this->requireDependencies();

        // set the configuration for the shop
        $this->config->setShop($shopId);

        # either use the test or the live api key
        # depending on our sub shop configuration
        $apiKey = ($this->config->isTestmodeActive()) ? $this->config->getTestApiKey() : $this->config->apiKey();

        return $this->buildApiClient(
            $apiKey
        );
    }

    /**
     * @param null $shopId
     * @return MollieApiClient
     * @throws ApiException
     */
    public function createLiveClient($shopId = null)
    {
        $this->requireDependencies();

        // set the configuration for the shop
        $this->config->setShop($shopId);

        return $this->buildApiClient(
            $this->config->apiKey()
        );
    }

    /**
     * @param null $shopId
     * @return MollieApiClient
     * @throws ApiException
     */
    public function createTestClient($shopId = null)
    {
        $this->requireDependencies();

        // set the configuration for the shop
        $this->config->setShop($shopId);

        return $this->buildApiClient(
            $this->config->getTestApiKey()
        );
    }
    

    /**
     * @param $apiKey
     * @return MollieApiClient
     * @throws ApiException
     */
    private function buildApiClient($apiKey)
    {
        $client = new MollieApiClient();

        // add platform name and version
        $client->addVersionString(
            'Shopware/' .
            Shopware()->Container()->getParameter('shopware.release.version')
        );

        // add plugin name and version
        $client->addVersionString(
            'MollieShopware/' . MollieShopware::PLUGIN_VERSION
        );

        // set the api key based on the configuration
        $client->setApiKey($apiKey);

        return $client;
    }

    /**
     *
     */
    private function requireDependencies()
    {
        // Load composer libraries
        if (file_exists(__DIR__ . '/../Client/vendor/scoper-autoload.php')) {
            require_once __DIR__ . '/../Client/vendor/scoper-autoload.php';
        }

        // Load guzzle functions
        if (file_exists(__DIR__ . '/../Client/vendor/guzzlehttp/guzzle/src/functions_include.php')) {
            require_once __DIR__ . '/../Client/vendor/guzzlehttp/guzzle/src/functions_include.php';
        }

        // Load promises functions
        if (file_exists(__DIR__ . '/../Client/vendor/guzzlehttp/promises/src/functions_include.php')) {
            require_once __DIR__ . '/../Client/vendor/guzzlehttp/promises/src/functions_include.php';
        }

        // Load psr7 functions
        if (file_exists(__DIR__ . '/../Client/vendor/guzzlehttp/psr7/src/functions_include.php')) {
            require_once __DIR__ . '/../Client/vendor/guzzlehttp/psr7/src/functions_include.php';
        }

        // Load client
        if (file_exists(__DIR__ . '/../Client/vendor/mollie/mollie-api-php/src/MollieApiClient.php')) {
            require_once __DIR__ . '/../Client/vendor/mollie/mollie-api-php/src/MollieApiClient.php';
        }
    }

}
