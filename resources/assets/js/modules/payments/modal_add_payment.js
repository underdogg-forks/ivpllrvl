(function ($) {
    'use strict';

    $(function () {
        const configNode = document.getElementById('js-modal-add-payment-config');
        if (!configNode) {
            return;
        }

        const addPaymentUrl = configNode.dataset.addPaymentUrl;
        const paymentFormUrl = configNode.dataset.paymentFormUrl;
        const returnUrl = configNode.dataset.returnUrl;
        const ipDebug = Number.parseInt(configNode.dataset.ipDebug || '0', 10);

        $('#enter-payment').modal('show');
        $('#enter-payment').on('shown', function () {
            $('#payment_amount').trigger('focus');
        });

        $('.simple-select').select2();

        $('#btn_modal_payment_submit').on('click', function () {
            $.post(addPaymentUrl, {
                invoice_id: $('#invoice_id').val(),
                payment_amount: $('#payment_amount').val(),
                payment_method_id: $('#payment_method_id').val(),
                payment_date: $('#payment_date').val(),
                payment_note: $('#payment_note').val(),
            }, function (data) {
                const response = json_parse(data, ipDebug);

                if (response.success === 1) {
                    if ($('#payment_cf_exist').val() === 'yes') {
                        window.location = `${paymentFormUrl}/${response.payment_id}`;
                    } else {
                        window.location = returnUrl;
                    }
                    return;
                }

                $('.control-group').removeClass('has-error');
                for (const key in response.validation_errors) {
                    if (Object.prototype.hasOwnProperty.call(response.validation_errors, key)) {
                        $('#' + key).parent().parent().addClass('has-error');
                    }
                }
            });
        });
    });
})(jQuery);
