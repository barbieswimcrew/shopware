<?php

use Shopware\Components\Password\Manager;

class Shopware_Controllers_Frontend_RegisterGuest extends Shopware_Controllers_Frontend_Payment
{

    public function indexAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->createAccount($this->dataProvider());
    }

    private function createAccount($details)
    {
        $module = $this->container->get('modules')->Admin();
        $session = $this->container->get('session');

        /** @var Manager $encoder */
        $encoder = $this->container->get('passwordencoder');

        /*
         * build data
         */
        $gateway = $this->container->get('mollie_shopware.components.apple_pay_direct.gateway.dbal.register_guest_customer_gateway');
        $paymentMeanId = $gateway->getPaymentMeanId();

        $data['auth']['accountmode'] = '1';
        $data['auth']['email'] = $details['EMAIL'];
        $data['auth']['password'] = $details['PAYERID'];
        $data['auth']['passwordMD5'] = $gateway->getPasswordMD5($data['auth']['email']);
        $data['billing']['city'] = $details['CITY'];
        $data['billing']['company'] = $details['COMPANY'];
        $data['billing']['country'] = $details['COUNTRYID'];
        $data['billing']['customer_type'] = $details['CUSTOMER_TYPE'];
        $data['billing']['department'] = $details['DEPARTMENT'];
        $data['billing']['firstname'] = $details['FIRSTNAME'];
        $data['billing']['lastname'] = $details['LASTNAME'];
        $data['billing']['salutation'] = $details['SALUTATION'];
        $data['billing']['stateID'] = $details['STATEID'];
        $data['billing']['street'] = $details['STREET'];
        $data['billing']['streetnumber'] = $details['STREETNUMBER'];
        $data['payment']['object'] = $gateway->getPaymentMeanById($paymentMeanId);
        $data['billing']['phone'] = $details['PHONE'];
        $data['billing']['zipcode'] = $details['ZIPCODE'];
        $data['shipping'] = $data['billing'];

        // First try login / Reuse apple pay account
        $module->sSYSTEM->_POST = $data['auth'];
        $module->sLogin(true);

        // Check login status
        if ($module->sCheckUser()) {
            $gateway->updateShipping($session->offsetGet('sUserId'), $data['shipping']);

            $module->sSYSTEM->_POST = ['sPayment' => $paymentMeanId];
            $module->sUpdatePayment();
            echo 'user updated';
        } else {
            $encoderName = $encoder->getDefaultPasswordEncoderName();
            $data['auth']['encoderName'] = $encoderName;
            $data['auth']['password'] = $encoder->getEncoderByName($encoderName)->encodePassword($data['auth']['password']);

            $session->offsetSet('sRegisterFinished', false);

            $gateway->saveUser($data['auth'], $data['shipping']);
            $module->sSYSTEM->_POST = $data['auth'];
            $module->sLogin(true);
            echo 'user created';
        }
    }

    private function dataProvider()
    {
        return [
            'EMAIL' => 'ms@dasistweb.de',
            'PAYERID' => '0815',
            'SALUTATION' => 'mr', // or 'ms'
            'FIRSTNAME' => 'Martin',
            'LASTNAME' => 'Schindler',
            'STREET' => 'Sonnental',
            'STREETNUMBER' => '7a',
            'ZIPCODE' => '83677',
            'CITY' => 'Greiling',
            'COUNTRYID' => 2, // '2' for 'Germany'
            'STATEID' => 6, // '6' for 'Bavaria'
            'CUSTOMER_TYPE' => 'private', // or 'business'
            'COMPANY' => '',
            'DEPARTMENT' => '',
            'PHONE' => '0800123456',
        ];
    }
}