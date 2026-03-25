<div
    id="js-modal-product-lookups-config"
    data-process-selections-url="<?php echo site_url('products/ajax/process_product_selections'); ?>"
    data-lookups-url="<?php echo site_url('products/ajax/modal_product_lookups'); ?>"
    data-default-item-tax-rate="<?php echo $default_item_tax_rate; ?>"
    data-ip-debug="<?php echo (int) IP_DEBUG; ?>"
></div>
<script defer src="<?php echo base_url('assets/js/modules/products/modal_product_lookups.js'); ?>"></script>



<div id="modal-choose-items" class="modal col-xs-12 col-sm-10 col-sm-offset-1"
     role="dialog" aria-labelledby="modal-choose-items" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-close"></i></button>
            <h4 class="panel-title"><?php _trans('add_product'); ?></h4>
        </div>
        <div class="modal-body">

            <div class="form-inline">
                <div class="form-group filter-form">
                    <select name="filter_family" id="filter_family" class="form-control simple-select">
                        <option value=""><?php _trans('any_family'); ?></option>
                        <?php foreach ($families as $family) { ?>
                            <option value="<?php echo $family->family_id; ?>"
                                <?php if (isset($filter_family) && $family->family_id == $filter_family) {
                                    echo ' selected="selected"';
                                } ?>>
                                <?php _htmlsc($family->family_name); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="filter_product" id="filter_product"
                           placeholder="<?php _trans('product_name'); ?>"
                           value="<?php echo $filter_product ?>">
                </div>
                <button type="button" id="filter-button"
                        class="btn btn-default"><?php _trans('search_product'); ?></button>
                <button type="button" id="product-reset-button" class="btn btn-default">
                    <?php _trans('reset'); ?>
                </button>
            </div>

            <br/>

            <div id="product-lookup-table">
                <?php $this->layout->load_view('products/partial_product_table_modal'); ?>
            </div>

        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button class="select-items-confirm btn btn-success" type="button">
                    <i class="fa fa-check"></i>
                    <?php _trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i>
                    <?php _trans('cancel'); ?>
                </button>
            </div>
        </div>
    </form>

</div>
