define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function ($,
              Component,
              rendererList) {
        'use strict';

        var defaultComponent = 'Voguepay_Payment/js/view/payment/method-renderer/default';

        var methods = [
            // {type: 'voguepay_payment_bitcoin', component: defaultComponent},
            {type: 'voguepay_payment_creditcard_jp', component: defaultComponent},
        ];
        $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);