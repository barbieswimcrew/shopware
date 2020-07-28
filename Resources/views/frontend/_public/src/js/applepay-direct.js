(function ($) {
    "use strict";

    var applePayApiVersion = 3;
    var applePayButtonSelector = '.applepay-button';

    $(document).ready(function () {

        if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
            return;
        }

        var buttons = document.querySelectorAll(applePayButtonSelector);

        buttons.forEach(function (button) {

            // Display the apple pay button
            button.style.display = 'inline-block';

            button.addEventListener('click', function (e) {

                const session = createApplePaySession(
                    button.dataset.label,
                    button.dataset.amount,
                    button.dataset.country,
                    button.dataset.currency
                );

                session.onshippingcontactselected = function (e) {
                    console.log(e);

                    let shippingOptions = [
                        {label: 'Standard Shipping', amount: 33, detail: '3-5 days', identifier: 'domestic_std'}, {label: 'Expedited Shipping', amount: 45, detail: '1-3 days', identifier: 'domestic_exp'}
                    ];

                    var newTotal = {type: 'final', label: '<?=PRODUCTION_DISPLAYNAME?>', amount: 23};
                    var newLineItems = [{type: 'final', label: 'test', amount: 22}, {type: 'final', label: 'P&P', amount: 22}];

                    session.completeShippingContactSelection(
                        ApplePaySession.STATUS_SUCCESS,
                        shippingOptions,
                        newTotal,
                        newLineItems
                    );
                };

                session.onshippingmethodselected = function (e) {
                    console.log(e);

                    let shippingOptions = [
                        {label: 'Standard Shipping', amount: 33, detail: '3-5 days', identifier: 'domestic_std'}, {label: 'Expedited Shipping', amount: 45, detail: '1-3 days', identifier: 'domestic_exp'}
                    ];


                    var newTotal = {type: 'final', label: '<?=PRODUCTION_DISPLAYNAME?>', amount: 22};
                    var newLineItems = [{type: 'final', label: 'test', amount: 22}, {type: 'final', label: 'P&P', amount: 22}];

                    session.completeShippingContactSelection(
                        ApplePaySession.STATUS_SUCCESS,
                        shippingOptions,
                        newTotal,
                        newLineItems
                    );
                };

                session.onpaymentmethodselected = function (e) {
                    console.log(e);
                    var newTotal = {type: 'final', label: '<?=PRODUCTION_DISPLAYNAME?>', amount: 23};
                    var newLineItems = [{type: 'final', label: 'asdf', amount: 33}, {type: 'final', label: 'P&P', amount: 23}];

                    session.completePaymentMethodSelection(newTotal, newLineItems);
                };

                session.oncancel = function () {
                    console.log('Apple Pay: onCancel');
                };

                session.onvalidatemerchant = function (e) {
                    console.log('Apple Pay: Validating Merchant Session');
                    $.post(
                        button.dataset.validationurl,
                        {
                            validationUrl: e.validationURL
                        }
                    ).done(function (validationData) {
                            validationData = JSON.parse(validationData);
                            session.completeMerchantValidation(validationData);
                            console.log('verified');
                        }
                    ).fail(function (xhr, status, error) {
                        console.log('Apple Pay Error: ' + error);
                        session.abort();
                    });
                };

                session.onpaymentauthorized = function (e) {
                    console.log('Apple Pay: Authorized');
                    let paymentToken = e.payment.token;
                    paymentToken = JSON.stringify(paymentToken);

                    console.log('Apple Pay Token: ' + paymentToken);

                    createAddProductForm(
                        button.dataset.checkouturl,
                        paymentToken,
                        button.dataset.number,
                        button.dataset.qty
                    );
                };

                session.begin();
            });
        });
    });

    /**
     *
     * @param label
     * @param amount
     * @param country
     * @param currency
     * @returns {ApplePaySession}
     */
    function createApplePaySession(label, amount, country, currency) {
        const request = {
            countryCode: country,
            currencyCode: currency,
            requiredShippingContactFields: ['postalAddress'],
            supportedNetworks: [
                'amex',
                'maestro',
                'masterCard',
                'visa',
                'vPay'
            ],
            merchantCapabilities: ['supports3DS', 'supportsEMV', 'supportsCredit', 'supportsDebit'],
            total: {
                label: label,
                amount: amount
            },
            lineItems: [
                {
                    "label": "Bag Subtotal",
                    "type": "final",
                    "amount": "35.00"
                },
                {
                    "label": "Free Shipping",
                    "amount": "0.00",
                    "type": "final"
                },
                {
                    "label": "Estimated Tax",
                    "amount": "3.06",
                    "type": "final"
                }
            ]
        };

        return new ApplePaySession(applePayApiVersion, request);
    }


    /**
     *
     * @param checkoutURL
     * @param paymentToken
     * @param productNumber
     * @param quantity
     */
    function createAddProductForm(checkoutURL, paymentToken, productNumber, quantity) {

        let token = '';

        if (CSRF.checkToken()) {
            token = CSRF.getToken();
        }

        var me = this,
            $form,
            createField = function (name, val) {
                return $('<input>', {
                    type: 'hidden',
                    name: name,
                    value: val
                });
            };

        $form = $('<form>', {
            action: checkoutURL,
            method: 'POST'
        });

        createField('addProduct', true).appendTo($form);
        createField('productNumber', productNumber).appendTo($form);
        createField('productQuantity', quantity).appendTo($form);
        createField('paymentToken', paymentToken).appendTo($form);
        createField('__csrf_token', token).appendTo($form);

        $form.appendTo($('body'));
        $form.submit();
    }

}(jQuery));
