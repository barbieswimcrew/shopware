<?php

namespace MollieShopware\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_Event_EventArgs;
use Enlight_View;
use Exception;
use MollieShopware\Components\ApplePayDirect\ApplePayDirect;
use MollieShopware\Components\Config;
use MollieShopware\Components\Constants\PaymentMethod;
use MollieShopware\Components\Services\PaymentMethodService;
use Shopware\Components\Theme\LessDefinition;
use Shopware\Models\Payment\Payment;

class FrontendViewSubscriber implements SubscriberInterface
{

    const APPLEPAY_DIRECT_NAME = 'mollie_' . PaymentMethod::APPLEPAY_DIRECT;

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'addComponentsVariables',
            'Enlight_Controller_Action_PreDispatch_Frontend' => 'addViewDirectory',
            'Enlight_Controller_Action_PostDispatch_Frontend' => 'onFrontendPostDispatch',
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'getController',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onFrontendCheckoutShippingPaymentPostDispatch',
            'Theme_Compiler_Collect_Plugin_Javascript' => 'onCollectJavascript',
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectLess',
        ];
    }

    /**
     * Add plugin view dir to Smarty
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function addComponentsVariables(Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Action $controller */
        $controller = null;

        /** @var string|null $controllerName */
        $controllerName = null;

        /** @var Enlight_View $view */
        $view = null;

        if (method_exists($args, 'getSubject')) {
            $controller = $args->getSubject();
            $controllerName = $controller->Request()->getControllerName();
        }

        if ($controller !== null) {
            $view = $controller->View();
        }

        /** @var Config $config */
        $config = Shopware()->Container()->get('mollie_shopware.config');

        if ($controllerName === 'checkout' && $config !== null && $view !== null) {
            $view->assign('sMollieEnableComponent', $config->enableCreditCardComponent());
            $view->assign('sMollieEnableComponentStyling', $config->enableCreditCardComponentStyling());
        }
    }

    /**
     * Add plugin view dir to Smarty
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function addViewDirectory(Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Action $controller */
        $controller = null;

        /** @var Enlight_View $view */
        $view = null;

        if (method_exists($args, 'getSubject')) {
            $controller = $args->getSubject();
        }

        if ($controller !== null) {
            $view = $controller->View();
        }

        if ($view !== null) {
            $view->addTemplateDir(__DIR__ . '/../Resources/views');
        }
    }

    /**
     * Get error messages from session and assign them to the frontend view
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function getController(Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Components_Session_Namespace $session */
        $session = Shopware()->Session();

        /** @var Enlight_Controller_Action $controller */
        $controller = $args->getSubject();

        /** @var Enlight_View $view */
        $view = null;

        if (!empty($controller)) {
            $view = $controller->view();
        }

        if ($session !== null && $view !== null &&
            ($session->mollieError || $session->mollieStatusError)) {
            // assign errors to view
            $view->assign('sMollieError', $session->mollieError);
            $view->assign('sMollieStatusError', $session->mollieStatusError);

            // unset error, so it wont show up on next page view
            $session->mollieStatusError = $session->mollieError = null;
        }
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
        $applePay->addViewData($request, $view);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onFrontendCheckoutShippingPaymentPostDispatch(Enlight_Event_EventArgs $args)
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
     * Collects javascript files.
     *
     * @param Enlight_Event_EventArgs $args
     * @return ArrayCollection
     */
    public function onCollectJavascript(Enlight_Event_EventArgs $args)
    {
        // Create new array collection to add src files
        $collection = new ArrayCollection();

        // Add the javascript files to the collection
        $collection->add(__DIR__ . '/../Resources/views/frontend/_public/src/js/applepay-direct.js');

        return $collection;
    }

    /**
     * Collects Less files
     *
     * @param Enlight_Event_EventArgs $args
     * @return ArrayCollection
     */
    public function onCollectLess(Enlight_Event_EventArgs $args)
    {
        $lessFiles = [];
        $lessFiles[] = __DIR__ . '/../Resources/views/frontend/_public/src/less/apple-pay-buttons.less';
        $lessFiles[] = __DIR__ . '/../Resources/views/frontend/_public/src/less/checkout.less';
        $lessFiles[] = __DIR__ . '/../Resources/views/frontend/_public/src/less/components.less';

        $less = new LessDefinition(
            [], // configuration
            $lessFiles, // less files to compile
            __DIR__
        );

        return new ArrayCollection([$less]);
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
