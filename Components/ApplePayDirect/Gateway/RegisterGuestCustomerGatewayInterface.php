<?php

namespace MollieShopware\Components\ApplePayDirect\Gateway;

interface RegisterGuestCustomerGatewayInterface
{

    public function getPaymentMeanId();

    public function getPaymentMeanById($paymentMeanId);

    public function getPasswordMD5($email);

    public function updateShipping($userId, $shippingData);

    public function saveUser($auth, $shipping);
}