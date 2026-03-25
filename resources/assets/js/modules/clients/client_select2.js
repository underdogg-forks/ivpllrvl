(function ($) {
    'use strict';

    $(function () {
        const configNode = document.getElementById('js-client-select2-config');
        if (!configNode) {
            return;
        }

        const placeholder = configNode.dataset.placeholder;
        const nameQueryUrl = configNode.dataset.nameQueryUrl;
        const savePreferenceUrl = configNode.dataset.savePreferenceUrl;
        const csrfTokenName = configNode.dataset.csrfTokenName;
        const csrfCookieName = configNode.dataset.csrfCookieName;

        $('.client-id-select').select2({
            placeholder,
            ajax: {
                url: nameQueryUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        query: params.term,
                        permissive_search_clients: $('input#input_permissive_search_clients').val(),
                        page: params.page,
                        [csrfTokenName]: Cookies.get(csrfCookieName),
                    };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: true,
            },
            minimumInputLength: 1,
        });

        $('#toggle_permissive_search_clients').on('click', function () {
            const input = $('input#input_permissive_search_clients');
            const icon = $('span#toggle_permissive_search_clients i');
            const isEnabled = input.val() === '1';
            const nextValue = isEnabled ? '0' : '1';

            $.get(savePreferenceUrl, { permissive_search_clients: nextValue });
            input.val(nextValue);

            icon.toggleClass('fa-toggle-on', nextValue === '1');
            icon.toggleClass('fa-toggle-off', nextValue !== '1');
        });
    });
})(jQuery);
