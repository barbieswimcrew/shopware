<?php

namespace MollieShopware\Components\ApplePayDirect\Gateway;

interface RegisterGuestCustomerGatewayInterface
{

    /**
     * @return mixed
     */
    public function getPaymentMeanId();

    /**
     * @param $paymentMeanId
     * @return mixed
     */
    public function getPaymentMeanById($paymentMeanId);

    /**
     * @param $email
     * @return mixed
     */
    public function getPasswordMD5($email);

    /**
     * @param $userId
     * @param $shippingData
     * @return mixed
     */
    public function updateShipping($userId, $shippingData);

    /**
     * @param $auth
     * @param $shipping
     * @return mixed
     */
    public function saveUser($auth, $shipping);

    /**
     * @param $email
     * @return mixed
     */
    public function getGuest($email);

}