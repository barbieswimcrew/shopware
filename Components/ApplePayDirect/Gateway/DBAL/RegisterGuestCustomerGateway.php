<?php

namespace MollieShopware\Components\ApplePayDirect\Gateway\DBAL;

use Doctrine\ORM\EntityManagerInterface;
use MollieShopware\Components\ApplePayDirect\Gateway\RegisterGuestCustomerGatewayInterface;
use Shopware\Bundle\AccountBundle\Form\Account\AddressFormType;
use Shopware\Bundle\AccountBundle\Form\Account\PersonalFormType;
use Shopware\Bundle\AccountBundle\Service\AddressServiceInterface;
use Shopware\Bundle\AccountBundle\Service\RegisterServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Shop;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Symfony\Component\Form\FormFactoryInterface;

class RegisterGuestCustomerGateway implements RegisterGuestCustomerGatewayInterface
{

    /** @var AddressServiceInterface $addressService */
    private $addressService;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var FormFactoryInterface $formFactory */
    private $formFactory;

    private $modules;

    /** @var RegisterServiceInterface $registerService */
    private $registerService;

    /** @var Shop $shop */
    private $shop;

    public function __construct(
        AddressServiceInterface $addressService,
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        $modules,
        ContextServiceInterface $contextService,
        RegisterServiceInterface $registerService
    ) {
        $this->addressService = $addressService;
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->modules = $modules;
        $this->registerService = $registerService;
        $this->shop = $contextService->getShopContext()->getShop();
    }

    public function getPaymentMeanId()
    {
        $paymentMean = $this->em->getRepository(Payment::class)->findOneBy(
            [
                'name' => 'mollie_applepaydirect',
            ]
        );

        return $paymentMean->getId();
    }

    /**
     * @param $paymentMeanId
     * @return mixed
     */
    public function getPaymentMeanById($paymentMeanId)
    {
        return $this->modules->Admin()->sGetPaymentMeanById($paymentMeanId);
    }

    /**
     * @param string $email
     * @return mixed
     */
    public function getPasswordMD5($email)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('c.hashPassword')
            ->from(Customer::class, 'c')
            ->where($qb->expr()->like('c.email', ':email'))
            ->andWhere($qb->expr()->eq('c.active', 1))
            ->setParameter(':email', $email);

        if ($this->shop->hasCustomerScope()) {
            $qb->andWhere($qb->expr()->eq('c.shopId', $this->shop->getId()));
        }

        # Always use the latest account. It is possible, that the account already exists but the password may be invalid.
        # The plugin then creates a new account and uses that one instead.
        $qb->orderBy('c.id', 'DESC');

        return $qb->getQuery()->getArrayResult()[0]['hashPassword'];
    }

    public function updateShipping($userId, $shippingData)
    {
        /** @var Customer $customer */
        $customer = $this->em->getRepository(Customer::class)->findOneBy(['id' => $userId]);

        /** @var Address $address */
        $address = $customer->getDefaultShippingAddress();

        $form = $this->formFactory->create(AddressFormType::class, $address);
        $form->submit($shippingData);

        $this->addressService->update($address);
    }

    public function saveUser($auth, $shipping)
    {
        $plain = array_merge($auth, $shipping);

        //Create forms and validate the input
        $customer = new Customer();
        $form = $this->formFactory->create(PersonalFormType::class, $customer);
        $form->submit($plain);

        $address = new Address();
        $form = $this->formFactory->create(AddressFormType::class, $address);
        $form->submit($plain);

        $this->registerService->register($this->shop, $customer, $address, $address);
    }

}