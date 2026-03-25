(function ($) {
    'use strict';

    $(function () {
        const configNode = document.getElementById('js-task-form-config');
        if (!configNode) {
            return;
        }

        if (configNode.dataset.readonly === '1') {
            $('#task-form').find(':input').prop('disabled', true);
            $('#btn-submit').hide();
            $('#btn-cancel').prop('disabled', false);
        }
    });
})(jQuery);
