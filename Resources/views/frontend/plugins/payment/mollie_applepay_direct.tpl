{if $sMollieApplePayDirect.active}
    <a class="applepay-button"
       lang="{$smarty.server.HTTP_ACCEPT_LANGUAGE}"
       style="-webkit-appearance: -apple-pay-button; -apple-pay-button-type: check-out; display: none;"
       data-validationurl="{url module=widgets controller="MollieApplePayDirect" action="createPaymentSession" forceSecure}"
       data-checkouturl="{url module=widgets controller="MollieApplePayDirect" action="createPayment" forceSecure}"
       data-label="{$sMollieApplePayDirect.label}"
       data-amount="{$sMollieApplePayDirect.amount}"
       data-country="{$sMollieApplePayDirect.country}"
       data-currency="{$sMollieApplePayDirect.currency}"
        {if $sMollieApplePayDirect.mode == 'item'}
        {/if}
    ></a>
{/if}