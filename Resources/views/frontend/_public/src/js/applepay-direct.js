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

                //   session.onpaymentmethodselected = function (e) {
                //      console.log(e);
                //   };

                session.onshippingcontactselected = function (e) {
                    console.log(e);
                };

                session.onshippingmethodselected = function (e) {
                    console.log(e);
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
                }

                session.onpaymentauthorized = function (e) {
                    console.log('Apple Pay: Authorized');
                    let paymentToken = e.payment.token;
                    paymentToken = JSON.stringify(paymentToken);

                    console.log('Apple Pay Token: ' + paymentToken);

                    createAddProductForm(button.dataset.checkouturl, paymentToken);
                }

                session.begin();
            });
        });
    });

    function createApplePaySession(label, amount, country, currency) {
        var request = {
            countryCode: country,
            currencyCode: currency,
            supportedNetworks: [
                'amex',
                'maestro',
                'masterCard',
                'visa',
                'vPay'
            ],
            merchantCapabilities: ['supports3DS'],
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
     */
    function createAddProductForm(checkoutURL, paymentToken) {

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
        createField('productNumber', 'abc').appendTo($form);
        createField('productQuantity', 1).appendTo($form);
        createField('paymentToken', paymentToken).appendTo($form);
        createField('__csrf_token', token).appendTo($form);

        $form.appendTo($('body'));

        console.log('form created');

        $form.submit();
    }

}(jQuery));
