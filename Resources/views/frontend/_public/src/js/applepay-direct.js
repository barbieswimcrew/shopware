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

                    if (button.dataset.addproducturl) {
                        $.post(
                            button.dataset.addproducturl,
                            {
                                number: button.dataset.productnumber,
                                quantity: 1,
                            }
                        ).done(function (data) {
                            }
                        );
                    }


                    /**
                     *
                     * @param e
                     */
                    session.onshippingcontactselected = function (e) {
                        console.log(e);
                        $.get(
                            button.dataset.getshippingsurl
                        ).done(function (data) {
                                data = JSON.parse(data);
                                session.completeShippingContactSelection(
                                    ApplePaySession.STATUS_SUCCESS,
                                    data.shippingmethods,
                                    data.cart.total,
                                    data.cart.items
                                );
                            }
                        );
                    };

                    /**
                     *
                     * @param e
                     */
                    session.onshippingmethodselected = function (e) {
                        console.log(e);

                        $.post(
                            button.dataset.setshippingurl,
                            {
                                identifier: e.shippingMethod.identifier
                            }
                        ).done(function (data) {
                                data = JSON.parse(data);
                                session.completeShippingMethodSelection(
                                    ApplePaySession.STATUS_SUCCESS,
                                    data.cart.total,
                                    data.cart.items
                                );
                            }
                        );
                    };

                    /**
                     *
                     */
                    session.oncancel = function () {
                        $.get(
                            button.dataset.restorecarturl
                        );
                    };

                    /**
                     *
                     * @param e
                     */
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

                    /**
                     *
                     * @param e
                     */
                    session.onpaymentauthorized = function (e) {
                        console.log('Apple Pay: Authorized');
                        let paymentToken = e.payment.token;
                        paymentToken = JSON.stringify(paymentToken);

                        console.log('Apple Pay Token: ' + paymentToken);
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
                    amount: 0
                }
            };

            return new ApplePaySession(applePayApiVersion, request);
        }

    }
    (jQuery)
)
;
