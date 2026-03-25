(function ($) {
    'use strict';

    $(function () {
        const configNode = document.getElementById('js-modal-task-lookups-config');
        if (!configNode) {
            return;
        }

        const processSelectionsUrl = configNode.dataset.processSelectionsUrl;
        const defaultItemTaxRate = configNode.dataset.defaultItemTaxRate;
        const ipDebug = Number.parseInt(configNode.dataset.ipDebug || '0', 10);

        $('#modal-choose-items').modal('show');

        const selectedTasks = [];
        $('.item-task-id').each(function () {
            const currentVal = $(this).val();
            if (currentVal.length) {
                selectedTasks.push(Number.parseInt(currentVal, 10));
            }
        });

        let hiddenTasks = 0;
        $('.modal-task-id').each(function () {
            const currentId = Number.parseInt($(this).attr('id').replace('task-id-', ''), 10);
            if (selectedTasks.indexOf(currentId) !== -1) {
                $('#task-id-' + currentId).parent().parent().hide();
                hiddenTasks++;
            }
        });

        if (hiddenTasks >= $('.task-row').length) {
            $('#task-modal-submit').hide();
        }

        $('.select-items-confirm').on('click', function () {
            const taskIds = [];
            $("input[name='task_ids[]']:checked").each(function () {
                taskIds.push(Number.parseInt($(this).val(), 10));
            });

            if (!taskIds.length) {
                return;
            }

            $.post(processSelectionsUrl, { task_ids: taskIds }, function (data) {
                const items = json_parse(data, ipDebug);

                for (const key in items) {
                    if (!Object.prototype.hasOwnProperty.call(items, key)) {
                        continue;
                    }

                    if (!items[key].tax_rate_id) {
                        items[key].tax_rate_id = defaultItemTaxRate;
                    }

                    if ($('#item_table .item:last input[name=item_name]').val() !== '') {
                        $('#new_row').clone().appendTo('#item_table').removeAttr('id').addClass('item').show();
                    }

                    const lastItemRow = $('#item_table .item:last');
                    lastItemRow.find('input[name=item_task_id]').val(items[key].task_id);
                    lastItemRow.find('input[name=item_name]').val(items[key].task_name);
                    lastItemRow.find('textarea[name=item_description]').val(items[key].task_description);
                    lastItemRow.find('input[name=item_price]').val(items[key].task_price);
                    lastItemRow.find('input[name=item_quantity]').val('1');
                    lastItemRow.find('select[name=item_tax_rate_id]').val(items[key].tax_rate_id);
                }

                $('#modal-choose-items').modal('hide');
                $('#invoice_change_client').hide();
                check_items_tax_usages();
            });
        });

        $('#tasks_table tr').on('click', function (event) {
            if (event.target.type !== 'checkbox') {
                $(':checkbox', this).trigger('click');
            }
        });
    });
})(jQuery);
