{if $sMollieApplePayDirectButton.active}
    <a class="applepay-button"
       lang="{$smarty.server.HTTP_ACCEPT_LANGUAGE}"
       style="-webkit-appearance: -apple-pay-button; -apple-pay-button-type: check-out; display: none;"
       data-carturl="{url module=widgets controller="MollieApplePayDirect" action="getCart" forceSecure}"
       data-getshippingsurl="{url module=widgets controller="MollieApplePayDirect" action="getShippings" forceSecure}"
       data-setshippingurl="{url module=widgets controller="MollieApplePayDirect" action="setShipping" forceSecure}"
       data-restorecarturl="{url module=widgets controller="MollieApplePayDirect" action="restoreCart" forceSecure}"
       data-validationurl="{url module=widgets controller="MollieApplePayDirect" action="createPaymentSession" forceSecure}"
       data-checkouturl="{url module=widgets controller="MollieApplePayDirect" action="createPayment" forceSecure}"
       data-label="{$sMollieApplePayDirectButton.label}"
       data-amount="{$sMollieApplePayDirectButton.amount}"
       data-country="{$sMollieApplePayDirectButton.country}"
       data-currency="{$sMollieApplePayDirectButton.currency}"
            {if $sMollieApplePayDirectButton.itemMode}
                data-productnumber="{$sMollieApplePayDirectButton.addNumber}"
            {/if}
    ></a>
{/if}