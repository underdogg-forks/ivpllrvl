<div
    id="js-client-select2-config"
    data-placeholder="<?php echo trans('client'); ?>"
    data-name-query-url="<?php echo site_url('clients/ajax/name_query'); ?>"
    data-save-preference-url="<?php echo site_url('clients/ajax/save_preference_permissive_search_clients'); ?>"
    data-csrf-token-name="<?php echo config_item('csrf_token_name'); ?>"
    data-csrf-cookie-name="<?php echo config_item('csrf_cookie_name'); ?>"
></div>
<script defer src="<?php echo base_url('assets/js/modules/clients/client_select2.js'); ?>"></script>
