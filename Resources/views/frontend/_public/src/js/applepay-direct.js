(function ($) {
    "use strict";

    var applePayApiVersion = 3;
    var applePayButtonSelector = '.applepay-button';
    var merchantIdentifier = 'https://www.dasistweb.de/de/';

    $(document).ready(function () {

        /**
         * Verify if Apple Pay JS API is available and
         * whether the device supports Apple Pay
         */
        if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
            return;
        }

        var buttons = document.querySelectorAll(applePayButtonSelector);

        buttons.forEach(function (button) {

            // Display the apple pay button
            button.style.display = 'inline-block';

            button.addEventListener('click', function (e) {
                var session = createApplePaySession(
                    button.dataset.label,
                    button.dataset.amount,
                    button.dataset.country,
                    button.dataset.currency
                );

                session.onvalidatemerchant = function (e) {
                    $.post(
                        button.dataset.url,
                        {
                            domain: button.dataset.domain,
                            validationUrl: e.validationURL
                        }
                    ).done(function (validationData) {
                            validationData = JSON.parse(validationData);
                            session.completeMerchantValidation(validationData);
                        }
                    ).fail(function (xhr, status, error) {
                        session.abort();
                    });
                }

                session.onpaymentauthorized = function (e) {
                    const payment = e.payment;
                    let token = e.payment.token;
                    token = JSON.stringify(token);
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
        };
        return new ApplePaySession(applePayApiVersion, request);
    }

}(jQuery));