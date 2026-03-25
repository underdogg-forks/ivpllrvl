<?php
// Script to generate all 11 remaining test files

$tests = [
    [
        'file' => 'modules/invoices/tests/InvoiceGroupsControllerTest.php',
        'namespace' => 'Modules\InvoiceGroups\Tests',
        'controller_class' => 'Modules\InvoiceGroups\Controllers\InvoiceGroupsController',
        'controller_name' => 'InvoiceGroupsController',
        'methods' => ['index', 'form', 'delete'],
        'entity' => 'invoice_group',
        'entity_plural' => 'invoice_groups',
        'route' => 'invoice_groups'
    ],
    [
        'file' => 'modules/invoices/tests/InvoicesAjaxControllerTest.php',
        'namespace' => 'Modules\Invoices\Tests',
        'controller_class' => 'Modules\Invoices\Controllers\InvoicesAjaxController',
        'controller_name' => 'InvoicesAjaxController',
        'methods' => ['save', 'save_invoice_tax_rate', 'delete_item', 'get_item', 'modal_copy_invoice', 
                     'copy_invoice', 'modal_change_user', 'change_user', 'modal_change_client', 
                     'change_client', 'modal_create_invoice', 'create', 'create_recurring', 
                     'modal_create_recurring', 'get_recur_start_date', 'modal_create_credit', 'create_credit'],
        'entity' => 'invoice',
        'entity_plural' => 'invoices',
        'route' => 'invoices',
        'is_ajax' => true
    ],
    [
        'file' => 'modules/invoices/tests/RecurringControllerTest.php',
        'namespace' => 'Modules\Invoices\Tests',
        'controller_class' => 'Modules\Invoices\Controllers\RecurringController',
        'controller_name' => 'RecurringController',
        'methods' => ['index', 'stop', 'delete'],
        'entity' => 'invoice_recurring',
        'entity_plural' => 'recurring_invoices',
        'route' => 'invoices/recurring'
    ],
];

echo "Test files configuration loaded.\n";
echo "Total files to generate: " . count($tests) . "\n";
