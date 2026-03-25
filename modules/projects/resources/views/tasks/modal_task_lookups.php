<div
    id="js-modal-task-lookups-config"
    data-process-selections-url="<?php echo site_url('tasks/ajax/process_task_selections'); ?>"
    data-default-item-tax-rate="<?php echo $default_item_tax_rate; ?>"
    data-ip-debug="<?php echo (int) IP_DEBUG; ?>"
></div>
<script defer src="<?php echo base_url('assets/js/modules/projects/modal_task_lookups.js'); ?>"></script>



<div id="modal-choose-items" class="modal col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2"
     role="dialog" aria-labelledby="modal-choose-items" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-close"></i></button>
            <h4 class="panel-title"><?php _trans('add_task'); ?></h4>
        </div>

        <div class="modal-body">
            <?php $this->layout->load_view('tasks/partial_task_table_modal'); ?>
        </div>

        <div class="modal-footer">
            <div class="btn-group">
                <button id="task-modal-submit" class="select-items-confirm btn btn-success" type="button">
                    <i class="fa fa-check"></i>
                    <?php echo lang('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i>
                    <?php echo lang('cancel'); ?>
                </button>
            </div>
        </div>

    </form>

</div>
