{if $sMollieApplePayDirect.active}
    <a class="applepay-button"
       lang="{$smarty.server.HTTP_ACCEPT_LANGUAGE}"
       style="-webkit-appearance: -apple-pay-button; -apple-pay-button-type: check-out; display: none;"
       data-validationUrl="{url controller="Mollie" action="requestApplePayPaymentSession" forceSecure}"
       data-domain="{$sMollieApplePayDirect.domain}"
       data-label="{$sMollieApplePayDirect.label}"
       data-amount="{$sMollieApplePayDirect.amount}"
       data-country="{$sMollieApplePayDirect.country}"
       data-currency="{$sMollieApplePayDirect.currency}"
    ></a>
{/if}