(function ($) {
    'use strict';

    $(function () {
        const configNode = document.getElementById('js-payment-form-config');
        if (!configNode) {
            return;
        }

        const amounts = json_parse(configNode.dataset.amounts || '{}', Number.parseInt(configNode.dataset.ipDebug || '0', 10));
        const invoicePaymentMethods = json_parse(configNode.dataset.invoicePaymentMethods || '{}', Number.parseInt(configNode.dataset.ipDebug || '0', 10));

        const $invoiceId = $('#invoice_id');
        $invoiceId.trigger('focus');

        $invoiceId.on('change', function () {
            const invoiceIdentifier = `invoice${$invoiceId.val()}`;
            $('#payment_amount').val((amounts[invoiceIdentifier] || '').replace('&nbsp;', ' '));
            $('#payment_method_id').val(invoicePaymentMethods[invoiceIdentifier]).trigger('change');

            if (invoicePaymentMethods[invoiceIdentifier] !== 0) {
                $('.payment-method-wrapper').append(`<input type="hidden" name="payment_method_id" id="payment-method-id-hidden" class="hidden" value="${invoicePaymentMethods[invoiceIdentifier]}">`);
                $('#payment_method_id').prop('disabled', true);
            } else {
                $('#payment-method-id-hidden').remove();
                $('#payment_method_id').prop('disabled', false);
            }
        });
    });
})(jQuery);
