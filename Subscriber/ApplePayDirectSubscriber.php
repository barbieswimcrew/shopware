<?php

namespace MollieShopware\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_View;
use MollieShopware\Components\ApplePayDirect\ApplePayDirect;
use MollieShopware\Components\Constants\PaymentMethod;
use Shopware\Components\Theme\LessDefinition;

class ApplePayDirectSubscriber implements SubscriberInterface
{

    const APPLEPAY_DIRECT_NAME = 'mollie_' . PaymentMethod::APPLEPAY_DIRECT;

    public static function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Javascript' => 'onCollectJavascript',
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectLess',
            'Enlight_Controller_Action_PostDispatch_Frontend' => 'onFrontendPostDispatch',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onFrontendCheckoutPostDispatch',
        ];
    }


    /**
     * @param Enlight_Event_EventArgs $args
     * @return ArrayCollection
     */
    public function onCollectJavascript(Enlight_Event_EventArgs $args)
    {
        $collection = new ArrayCollection();
        $collection->add(__DIR__ . '/../Resources/views/frontend/_public/src/js/applepay-direct.js');

        return $collection;
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @return ArrayCollection
     */
    public function onCollectLess(Enlight_Event_EventArgs $args)
    {
        $lessFiles = [];
        $lessFiles[] = __DIR__ . '/../Resources/views/frontend/_public/src/less/applepay-buttons.less';

        $less = new LessDefinition(
            [], // configuration
            $lessFiles, // less files to compile
            __DIR__
        );

        return new ArrayCollection([$less]);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onFrontendPostDispatch(Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Request_Request $request */
        $request = $args->getRequest();

        /** @var Enlight_View $view */
        $view = $args->getSubject()->View();

        # add the apple pay direct data for our current view.
        # the data depends on our page.
        # this might either be a product on PDP, or the full cart data
        $applePay = new ApplePayDirect(Shopware()->Shop());
        $applePay->addButtonStatus($request, $view);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onFrontendCheckoutPostDispatch(Enlight_Event_EventArgs $args)
    {
        if ($args->getRequest()->getActionName() !== 'shippingPayment') {
            return;
        }

        /** @var Enlight_View $view */
        $view = $args->getSubject()->View();

        $sPayments = $view->getAssign('sPayments');
        $this->removeApplePayDirectFromPaymentMeans($sPayments);

        $view->assign('sPayments', $sPayments);
    }

    /**
     * Remove "Apple Pay Direct" payment method from sPayments to avoid
     * that a user will be able to choose this payment method in the checkout
     * @param array $sPayments
     */
    private function removeApplePayDirectFromPaymentMeans(array &$sPayments)
    {
        foreach ($sPayments as $index => $payment) {
            if ($payment['name'] === self::APPLEPAY_DIRECT_NAME) {
                unset($sPayments[$index]);
                break;
            }
        }
    }

}