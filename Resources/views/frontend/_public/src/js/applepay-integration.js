(function($) {
    "use strict";

    var applePayApiVersion = 3;
    var applePayButtonSelector = '.applepay-button';
    var merchantIdentifier = 'https://www.dasistweb.de/de/';

    $(document).ready(function() {
        /**
         * Verify if Apple Pay JS API is available and
         * whether the device supports Apple Pay
         */
        if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
            return;
        }

        var buttons = document.querySelectorAll(applePayButtonSelector);

        buttons.forEach(function(button) {
            // Display the apple pay button
            button.style.display = 'inline-block';
            console.log(button.dataset.url);
            button.addEventListener('click', function(e) {
                var session = createApplePaySession();
                session.begin();


                session.onvalidatemerchant = function(e) {
                    console.log(e);
                    $.post(
                        button.dataset.url,
                        {
                            domain: 'mollie-local.diwc.de',
                            validationUrl: e.validationURL
                        }
                    ).done(function(response) {
                        console.log('success');
                        console.log(response);
                    }).fail(function() {
                        console.log('error');
                    });
                }
            });
        });
    });

    function createApplePaySession() {
        var request = {
            countryCode: 'DE',
            currencyCode: 'EUR',
            supportedNetworks: [
                'amex',
                'maestro',
                'masterCard',
                'visa',
                'vPay'
            ],
            merchantCapabilities: ['supports3DS'],
            total: {
                label: 'Your Merchant Name',
                amount: '10.00'
            },
        };
        return new ApplePaySession(applePayApiVersion, request);
    }

}(jQuery));