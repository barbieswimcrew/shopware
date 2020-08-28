<?php

namespace MollieShopware\Components\ApplePayDirect;

use MollieShopware\Components\ApplePayDirect\Handler\ApplePayDirectHandler;
use MollieShopware\Components\Config;
use MollieShopware\Components\MollieApiFactory;
use MollieShopware\Components\Services\PaymentMethodService;
use MollieShopware\Components\Shipping\Shipping;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayDirectFactory
{

    /**
     * @var \sAdmin
     */
    private $sAdmin;

    /**
     * @var \sBasket
     */
    private $sBasket;

    /**
     * @var Shipping $cmpShipping
     */
    private $cmpShipping;

    /**
     * @var PaymentMethodService $paymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    /** @var MollieApiFactory $apiFactory */
    private $apiFactory;

    /** @var Config $mollieConfig */
    private $mollieConfig;


    /**
     * @param $modules
     * @param Shipping $cmpShipping
     * @param PaymentMethodService $paymentMethodService
     * @param \Enlight_Components_Session_Namespace $session
     */
    public function __construct($modules, Shipping $cmpShipping, PaymentMethodService $paymentMethodService, \Enlight_Components_Session_Namespace $session, MollieApiFactory $apiFactory, Config $config)
    {
        $this->sAdmin = $modules->Admin();
        $this->sBasket = $modules->Basket();

        $this->cmpShipping = $cmpShipping;
        $this->paymentMethodService = $paymentMethodService;
        $this->session = $session;

        $this->apiFactory = $apiFactory;
        $this->mollieConfig = $config;
    }

    /**
     * @return ApplePayDirectHandler
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function create()
    {
        $applepay = new ApplePayDirectHandler(
            $this->apiFactory->createLiveClient(),
            $this->apiFactory->createTestClient(),
            $this->mollieConfig->isTestmodeActive(),
            $this->sAdmin,
            $this->sBasket,
            $this->cmpShipping,
            $this->paymentMethodService,
            $this->session
        );

        return $applepay;
    }

}