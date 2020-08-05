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
                        $.post(
                            button.dataset.getshippingsurl,
                            {
                                countryCode: e.shippingContact.countryCode,
                                postalCode: e.shippingContact.postalCode,
                            }
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
                        $.post(
                            button.dataset.validationurl,
                            {
                                validationUrl: e.validationURL
                            }
                        ).done(function (validationData) {
                                validationData = JSON.parse(validationData);
                                session.completeMerchantValidation(validationData);
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
                        console.log(e);
                        let paymentToken = e.payment.token;
                        paymentToken = JSON.stringify(paymentToken);

                        finishPayment(button.dataset.checkouturl, paymentToken, e.payment);
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
                requiredShippingContactFields: [
                    "postalAddress",
                    "name",
                    "phone",
                    "email"
                ],
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

        /**
         *
         * @param checkoutURL
         * @param paymentToken
         * @param payment
         */
        function finishPayment(checkoutURL, paymentToken, payment) {
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

            createField('paymentToken', paymentToken).appendTo($form);
            createField('street', payment.shippingContact.addressLines[0]).appendTo($form);
            createField('postalCode', payment.shippingContact.postalCode).appendTo($form);

            $form.appendTo($('body'));

            $form.submit();
        }

    }
    (jQuery)
)
;
