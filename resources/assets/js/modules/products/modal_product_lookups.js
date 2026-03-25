(function ($) {
    'use strict';

    $(function () {
        const configNode = document.getElementById('js-modal-product-lookups-config');
        if (!configNode) {
            return;
        }

        const processSelectionsUrl = configNode.dataset.processSelectionsUrl;
        const lookupsUrl = configNode.dataset.lookupsUrl;
        const defaultItemTaxRate = configNode.dataset.defaultItemTaxRate;
        const ipDebug = Number.parseInt(configNode.dataset.ipDebug || '0', 10);

        $('#modal-choose-items').modal('show');
        $('.simple-select').select2();

        const addClickTrToggleCheck = function () {
            $('#products_table tr').off('click.ip-products').on('click.ip-products', function (event) {
                if (event.target.type !== 'checkbox') {
                    $(':checkbox', this).trigger('click');
                }
            });
        };

        const refreshLookupTable = function (queryString) {
            const productTable = $('#product-lookup-table');
            productTable.html('<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>');

            let lookupUrl = `${lookupsUrl}/${Math.floor(Math.random() * 1000)}/?`;
            if (queryString) {
                lookupUrl += queryString;
            }

            window.setTimeout(function () {
                productTable.load(lookupUrl, addClickTrToggleCheck);
            }, 250);
        };

        const productsFilter = function () {
            const filterFamily = $('#filter_family').val();
            const filterProduct = $('#filter_product').val();
            const params = new URLSearchParams();

            if (filterFamily) {
                params.set('filter_family', filterFamily);
            }

            if (filterProduct) {
                params.set('filter_product', filterProduct);
            }

            refreshLookupTable(params.toString());
        };

        $('.select-items-confirm').on('click', function () {
            const productIds = [];

            $("input[name='product_ids[]']:checked").each(function () {
                productIds.push(Number.parseInt($(this).val(), 10));
            });

            if (!productIds.length) {
                return;
            }

            $.post(processSelectionsUrl, { product_ids: productIds }, function (data) {
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
                    lastItemRow.find('input[name=item_name]').val(items[key].product_name);
                    lastItemRow.find('textarea[name=item_description]').val(items[key].product_description);
                    lastItemRow.find('input[name=item_price]').val(items[key].product_price);
                    lastItemRow.find('input[name=item_quantity]').val('1');
                    lastItemRow.find('select[name=item_tax_rate_id]').val(items[key].tax_rate_id);
                    lastItemRow.find('input[name=item_product_id]').val(items[key].product_id);
                    lastItemRow.find('select[name=item_product_unit_id]').val(items[key].unit_id);
                }

                $('#modal-choose-items').modal('hide');
                check_items_tax_usages();
            });
        });

        $('#product-reset-button').on('click', function () {
            refreshLookupTable('reset_table=true');
        });

        $('#filter-button').on('click', productsFilter);
        $('#filter_family').on('change', productsFilter);

        $(document).on('keypress.ip-products-filter', function (event) {
            if (event.which === 13 && $('#filter_product').is(':focus')) {
                $('#filter-button').trigger('click');
                return false;
            }

            return true;
        });

        addClickTrToggleCheck();
    });
})(jQuery);
