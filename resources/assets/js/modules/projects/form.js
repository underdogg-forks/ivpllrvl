(function ($) {
    'use strict';

    $(function () {
        if (!$('#client_id').length) {
            return;
        }

        if (typeof window.init_client_select2 === 'function') {
            window.init_client_select2('#client_id');
        } else if (typeof window.setupClientSelect2 === 'function') {
            window.setupClientSelect2('#client_id');
        }
    });
})(jQuery);
