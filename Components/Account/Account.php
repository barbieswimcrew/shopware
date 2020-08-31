<?php

namespace MollieShopware\Components\Account;

use MollieShopware\Components\Account\Gateway\GuestAccountGatewayInterface;
use Shopware\Components\Password\Manager;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class Account
{

    /**
     * @var \sAdmin
     */
    private $admin;

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @var Manager $encoder
     */
    private $pwdEncoder;

    /**
     * @var GuestAccountGatewayInterface
     */
    private $gwGuestCustomer;


    /**
     * Account constructor.
     *
     * @param $modules
     * @param \Enlight_Components_Session_Namespace $session
     * @param Manager $pwdEncoder
     * @param GuestAccountGatewayInterface $gwGuestCustomer
     */
    public function __construct($modules, \Enlight_Components_Session_Namespace $session, Manager $pwdEncoder, GuestAccountGatewayInterface $gwGuestCustomer)
    {
        $this->admin = $modules->sAdmin();

        $this->session = $session;
        $this->pwdEncoder = $pwdEncoder;
        $this->gwGuestCustomer = $gwGuestCustomer;
    }


    /**
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param string $street
     * @param string $zip
     * @param string $city
     * @param int $countryID
     *
     * @throws \Enlight_Exception
     */
    public function createGuestAccount($email, $firstname, $lastname, $street, $zip, $city, $countryID)
    {
        $data['auth']['accountmode'] = '1';

        $data['auth']['email'] = $email;
        $data['auth']['password'] = $email; # just use email for this
        $data['auth']['passwordMD5'] = $this->gwGuestCustomer->getPasswordMD5($email);

        $data['billing']['company'] = '';
        $data['billing']['salutation'] = 'mr';
        $data['billing']['firstname'] = $firstname;
        $data['billing']['lastname'] = $lastname;
        $data['billing']['customer_type'] = 'private';
        $data['billing']['department'] = '';

        $data['billing']['street'] = $street;
        $data['billing']['streetnumber'] = '';
        $data['billing']['zipcode'] = $zip;
        $data['billing']['city'] = $city;
        $data['billing']['stateID'] = '';
        $data['billing']['country'] = $countryID;

        $paymentMeanId = $this->gwGuestCustomer->getPaymentMeanId();

        $data['payment']['object'] = $this->gwGuestCustomer->getPaymentMeanById($paymentMeanId);


        $data['shipping'] = $data['billing'];

        // First try login / Reuse apple pay account
        $this->execLogin($data['auth']);


        // Check login status
        if ($this->admin->sCheckUser()) {

            $this->gwGuestCustomer->updateShipping($this->session->offsetGet('sUserId'), $data['shipping']);

            $this->admin->sSYSTEM->_POST = ['sPayment' => $paymentMeanId];
            $this->admin->sUpdatePayment();

        } else {
            $encoderName = $this->pwdEncoder->getDefaultPasswordEncoderName();
            $data['auth']['encoderName'] = $encoderName;
            $data['auth']['password'] = $this->pwdEncoder->getEncoderByName($encoderName)->encodePassword($data['auth']['password']);

            $this->session->offsetSet('sRegisterFinished', false);

            $this->gwGuestCustomer->saveUser($data['auth'], $data['shipping']);

            $this->execLogin($data['auth']);
        }
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getGuestAccount($email)
    {
        return $this->gwGuestCustomer->getGuest($email);
    }

    /**
     * @param $authData
     * @throws \Exception
     */
    private function execLogin($authData)
    {
        $this->admin->sSYSTEM->_POST = $authData;

        $this->admin->sLogin(true);
    }

}
