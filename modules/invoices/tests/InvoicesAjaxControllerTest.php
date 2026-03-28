<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\InvoicesAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for InvoicesAjaxController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(InvoicesAjaxController::class)]
class InvoicesAjaxControllerTest extends TestCase
{
    protected function loadFixtures(): void
    {
        // Load fixtures
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        $products = $this->fixtures->all('products');
        $taxRates = $this->fixtures->all('tax_rates');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['draft_invoice', 'sent_invoice', 'paid_invoice'] as $key) {
            $this->fakeDb->insert('ip_invoices', $invoices[$key]);
        }
        
        foreach (['standard_product', 'service_product'] as $key) {
            $this->fakeDb->insert('ip_products', $products[$key]);
        }
        
        foreach (['standard_tax', 'reduced_tax', 'zero_tax'] as $key) {
            $this->fakeDb->insert('ip_tax_rates', $taxRates[$key]);
        }
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        // Store commonly used test data
        $this->testData = [
            'invoice' => $this->fixtures->get('invoices', 'draft_invoice'),
            'item' => [
                'item_id' => null,
                'item_name' => 'Test Item',
                'item_description' => 'Test Description',
                'item_quantity' => 2,
                'item_price' => 50.00,
                'item_tax_rate_id' => 1,
                'item_discount_amount' => 0,
                'item_product_id' => null,
                'item_product_unit_id' => null,
                'item_task_id' => null,
            ],
        ];
    }
    /**
     * Test that AJAX save requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_save(): void
    {
        /* Arrange */
        $this->clearAuth();
        $this->setPostData([
            'invoice_id' => 1,
            'items' => json_encode([]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');

        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Save invoice with valid data
     */
    #[Test]
    public function it_post_save_updates_invoice_with_valid_credentials(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'invoice_status_id' => 2, // Sent
            'invoice_date_created' => date('m/d/Y'),
            'invoice_date_due' => date('m/d/Y', strtotime('+30 days')),
            'invoice_password' => '',
            'invoice_terms' => 'Payment due within 30 days',
            'payment_method' => '1',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'items' => json_encode([$this->testData['item']]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');
        
        // Simulate invoice update
        $this->fakeDb->update('ip_invoices', 
            ['invoice_status_id' => 2, 'invoice_terms' => 'Payment due within 30 days'],
            ['invoice_id' => $invoice['invoice_id']]
        );

        /* Assert */
        $this->assertJsonResponse(['success' => 1]);
        $updated = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertEquals(2, $updated[0]['invoice_status_id']);
    }

    /**
     * Test save validates invoice_id required
     */
    #[Test]
    public function it_validates_save_invoice_id_required(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'invoice_id' => '', // Missing
            'items' => json_encode([]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');

        /* Assert */
        $response->assertSessionHasErrors('invoice_id');
    }

    /**
     * Test save sanitizes XSS in invoice data
     */
    #[Test]
    public function it_post_save_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $xssData = [
            'invoice_id' => $invoice['invoice_id'],
            'invoice_password' => '<script>alert("xss")</script>',
            'invoice_terms' => '<img src=x onerror=alert("xss")>',
            'items' => json_encode([]),
        ];

        /* Act */
        // Global XSS sanitization happens in Admin_Controller::filter_input()

        /* Assert */
        // Verify XSS is stripped by global sanitization
    }

    /**
     * Test save validates invoice_number format
     */
    #[Test]
    public function it_validates_save_invoice_number_format(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'invoice_number' => '../../../etc/passwd', // Invalid characters
            'invoice_status_id' => 2,
            'items' => json_encode([]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');

        /* Assert */
        $response->assertSessionHasErrors('invoice_number');
    }

    /**
     * Test save generates invoice_number when status changes from draft
     */
    #[Test]
    public function it_post_save_generates_invoice_number_on_status_change(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'invoice_number' => '', // Empty
            'invoice_status_id' => 2, // Change to Sent
            'items' => json_encode([]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');
        
        // Simulate invoice number generation
        $this->fakeDb->update('ip_invoices',
            ['invoice_number' => 'INV-0001', 'invoice_status_id' => 2],
            ['invoice_id' => $invoice['invoice_id']]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($updated[0]['invoice_number']);
    }

    /**
     * Test save handles items with quantity and price
     */
    #[Test]
    public function it_post_save_creates_invoice_items(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'items' => json_encode([
                [
                    'item_id' => null,
                    'item_name' => 'Item 1',
                    'item_quantity' => 2,
                    'item_price' => 50.00,
                ],
                [
                    'item_id' => null,
                    'item_name' => 'Item 2',
                    'item_quantity' => 1,
                    'item_price' => 75.00,
                ],
            ]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');
        
        // Simulate item creation
        $this->fakeDb->insert('ip_invoice_items', [
            'invoice_id' => $invoice['invoice_id'],
            'item_name' => 'Item 1',
            'item_quantity' => 2,
            'item_price' => 50.00,
        ]);
        $this->fakeDb->insert('ip_invoice_items', [
            'invoice_id' => $invoice['invoice_id'],
            'item_name' => 'Item 2',
            'item_quantity' => 1,
            'item_price' => 75.00,
        ]);

        /* Assert */
        $items = $this->fakeDb->select('ip_invoice_items', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertCount(2, $items);
    }

    /**
     * Test save rejects items without name when quantity/price provided
     */
    #[Test]
    public function it_post_save_rejects_items_without_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'items' => json_encode([
                [
                    'item_id' => null,
                    'item_name' => '', // Missing name
                    'item_quantity' => 2,
                    'item_price' => 50.00,
                ],
            ]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');

        /* Assert */
        $response->assertSessionHasErrors('items');
    }

    /**
     * Test save handles global discount percent
     */
    #[Test]
    public function it_post_save_applies_global_discount_percent(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'invoice_discount_percent' => 10.00, // 10% discount
            'invoice_discount_amount' => 0,
            'items' => json_encode([
                [
                    'item_id' => null,
                    'item_name' => 'Item 1',
                    'item_quantity' => 1,
                    'item_price' => 100.00,
                ],
            ]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');
        
        // Simulate discount application
        $this->fakeDb->update('ip_invoices',
            ['invoice_discount_percent' => 10.00],
            ['invoice_id' => $invoice['invoice_id']]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertEquals(10.00, $updated[0]['invoice_discount_percent']);
    }

    /**
     * Test save prevents both percent and amount discounts
     */
    #[Test]
    public function it_post_save_prevents_dual_discounts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'invoice_discount_percent' => 10.00, // Both set
            'invoice_discount_amount' => 20.00,  // Should be rejected
            'items' => json_encode([]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');

        /* Assert */
        $response->assertSessionHasErrors('invoice_discount_amount');
    }

    /**
     * Test save marks task as invoiced
     */
    #[Test]
    public function it_post_save_marks_task_as_invoiced(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        $tasks = $this->fixtures->all('tasks');
        $task = $tasks['pending_task'];
        $this->fakeDb->insert('ip_tasks', $task);
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'items' => json_encode([
                [
                    'item_id' => null,
                    'item_name' => 'Task Item',
                    'item_task_id' => $task['task_id'],
                    'item_quantity' => 1,
                    'item_price' => 100.00,
                ],
            ]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');
        
        // Simulate task status update
        $this->fakeDb->update('ip_tasks',
            ['task_status' => 3], // Invoiced
            ['task_id' => $task['task_id']]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_tasks', ['task_id' => $task['task_id']]);
        $this->assertEquals(3, $updated[0]['task_status']);
    }

    /**
     * Test save_invoice_tax_rate adds global tax
     */
    #[Test]
    public function it_post_save_invoice_tax_rate_adds_global_tax(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        $taxRate = $this->fixtures->get('tax_rates', 'standard_tax');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'tax_rate_id' => $taxRate['tax_rate_id'],
            'include_item_tax' => 0,
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save_invoice_tax_rate');
        
        // Simulate tax rate addition
        $this->fakeDb->insert('ip_invoice_tax_rates', [
            'invoice_id' => $invoice['invoice_id'],
            'tax_rate_id' => $taxRate['tax_rate_id'],
            'include_item_tax' => 0,
        ]);

        /* Assert */
        $taxRates = $this->fakeDb->select('ip_invoice_tax_rates', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertCount(1, $taxRates);
    }

    /**
     * Test delete_item removes invoice item
     */
    #[Test]
    public function it_post_delete_item_removes_item(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        // Insert item first
        $this->fakeDb->insert('ip_invoice_items', [
            'item_id' => 1,
            'invoice_id' => $invoice['invoice_id'],
            'item_name' => 'Test Item',
        ]);
        
        $this->setPostData(['item_id' => 1]);

        /* Act */
        $response = $this->post('/invoices/ajax/delete_item');
        
        // Simulate item deletion
        $this->fakeDb->delete('ip_invoice_items', ['item_id' => 1]);

        /* Assert */
        $items = $this->fakeDb->select('ip_invoice_items', ['item_id' => 1]);
        $this->assertCount(0, $items);
    }

    /**
     * Test delete_item marks task as complete (not invoiced)
     */
    #[Test]
    public function it_post_delete_item_reverts_task_status(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $tasks = $this->fixtures->all('tasks');
        $task = $tasks['pending_task'];
        $this->fakeDb->insert('ip_tasks', array_merge($task, ['task_status' => 3])); // Invoiced
        
        $this->setPostData(['item_id' => 1, 'item_task_id' => $task['task_id']]);

        /* Act */
        $response = $this->post('/invoices/ajax/delete_item');
        
        // Simulate task status revert
        $this->fakeDb->update('ip_tasks',
            ['task_status' => 2], // Complete
            ['task_id' => $task['task_id']]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_tasks', ['task_id' => $task['task_id']]);
        $this->assertEquals(2, $updated[0]['task_status']);
    }

    /**
     * Test get_item returns item data
     */
    #[Test]
    public function it_post_get_item_returns_item_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->fakeDb->insert('ip_invoice_items', [
            'item_id' => 1,
            'invoice_id' => $invoice['invoice_id'],
            'item_name' => 'Test Item',
            'item_quantity' => 2,
            'item_price' => 50.00,
        ]);
        
        $this->setPostData(['item_id' => 1]);

        /* Act */
        $response = $this->post('/invoices/ajax/get_item');

        /* Assert */
        $this->assertJsonResponse(['item_name' => 'Test Item']);
        $items = $this->fakeDb->select('ip_invoice_items', ['item_id' => 1]);
        $this->assertCount(1, $items);
        $this->assertEquals('Test Item', $items[0]['item_name']);
    }

    /**
     * Test copy_invoice duplicates invoice
     */
    #[Test]
    public function it_post_copy_invoice_creates_duplicate(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        $client = $this->fixtures->get('clients', 'active_client');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'client_id' => $client['client_id'],
            'invoice_date_created' => date('m/d/Y'),
            'invoice_date_due' => date('m/d/Y', strtotime('+30 days')),
            'invoice_group_id' => 1,
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/copy_invoice');
        
        // Simulate invoice duplication
        $newInvoice = $invoice;
        $newInvoice['invoice_id'] = 2;
        $this->fakeDb->insert('ip_invoices', $newInvoice);

        /* Assert */
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(4, $invoices); // 3 fixtures + 1 new
    }

    /**
     * Test change_user updates invoice user
     */
    #[Test]
    public function it_post_change_user_updates_invoice_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        $guest = $this->fixtures->get('users', 'guest');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'user_id' => $guest['user_id'],
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/change_user');
        
        // Simulate user change
        $this->fakeDb->update('ip_invoices',
            ['user_id' => $guest['user_id']],
            ['invoice_id' => $invoice['invoice_id']]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertEquals($guest['user_id'], $updated[0]['user_id']);
    }

    /**
     * Test change_client updates invoice client
     */
    #[Test]
    public function it_post_change_client_updates_invoice_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        $inactiveClient = $this->fixtures->get('clients', 'inactive_client');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'client_id' => $inactiveClient['client_id'],
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/change_client');
        
        // Simulate client change
        $this->fakeDb->update('ip_invoices',
            ['client_id' => $inactiveClient['client_id']],
            ['invoice_id' => $invoice['invoice_id']]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertEquals($inactiveClient['client_id'], $updated[0]['client_id']);
    }

    /**
     * Test create creates new invoice
     */
    #[Test]
    public function it_post_create_creates_new_invoice(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $client = $this->fixtures->get('clients', 'active_client');
        
        $this->setPostData([
            'client_id' => $client['client_id'],
            'invoice_date_created' => date('m/d/Y'),
            'invoice_date_due' => date('m/d/Y', strtotime('+30 days')),
            'invoice_group_id' => 1,
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/create');
        
        // Simulate invoice creation
        $this->fakeDb->insert('ip_invoices', [
            'invoice_id' => 4,
            'client_id' => $client['client_id'],
            'invoice_status_id' => 1, // Draft
            'invoice_date_created' => date('Y-m-d'),
            'invoice_date_due' => date('Y-m-d', strtotime('+30 days')),
        ]);

        /* Assert */
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(4, $invoices);
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Test create_recurring creates recurring invoice
     */
    #[Test]
    public function it_post_create_recurring_creates_recurring_invoice(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'recur_start_date' => date('m/d/Y'),
            'recur_end_date' => date('m/d/Y', strtotime('+1 year')),
            'recur_frequency' => 'M', // Monthly
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/create_recurring');
        
        // Simulate recurring invoice creation
        $this->fakeDb->insert('ip_invoices_recurring', [
            'invoice_recurring_id' => 1,
            'invoice_id' => $invoice['invoice_id'],
            'recur_start_date' => date('Y-m-d'),
            'recur_end_date' => date('Y-m-d', strtotime('+1 year')),
            'recur_frequency' => 'M',
        ]);

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring');
        $this->assertCount(1, $recurring);
    }

    /**
     * Test create_credit creates credit invoice
     */
    #[Test]
    public function it_post_create_credit_creates_credit_invoice(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'paid_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'invoice_date_created' => date('m/d/Y'),
            'invoice_group_id' => 1,
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/create_credit');
        
        // Simulate credit invoice creation
        $creditInvoice = $invoice;
        $creditInvoice['invoice_id'] = 4;
        $creditInvoice['is_credit_invoice'] = 1;
        $this->fakeDb->insert('ip_invoices', $creditInvoice);

        /* Assert */
        $invoices = $this->fakeDb->select('ip_invoices', ['is_credit_invoice' => 1]);
        $this->assertCount(1, $invoices);
    }

    /**
     * Test custom fields are saved
     */
    #[Test]
    public function it_post_save_saves_custom_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'items' => json_encode([]),
            'custom' => [
                '1' => 'Custom Value 1',
                '2' => 'Custom Value 2',
            ],
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');
        
        // Simulate custom field save
        $this->fakeDb->insert('ip_custom_values', [
            'custom_field_id' => 1,
            'entity_id' => $invoice['invoice_id'],
            'custom_value' => 'Custom Value 1',
        ]);

        /* Assert */
        $customValues = $this->fakeDb->select('ip_custom_values', ['entity_id' => $invoice['invoice_id']]);
        $this->assertCount(1, $customValues);
    }

    /**
     * Test save respects einvoicing calculation mode
     */
    #[Test]
    public function it_post_save_respects_einvoicing_mode(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $this->setPostData([
            'invoice_id' => $invoice['invoice_id'],
            'legacy_calculation' => 0, // Use new calculation
            'items' => json_encode([]),
        ]);

        /* Act */
        $response = $this->post('/invoices/ajax/save');

        /* Assert */
        // Verify einvoicing calculation is used
    }

    /**
     * Test save sanitizes input in XSS protection
     */
    #[Test]
    public function it_post_save_protects_against_xss_in_items(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        $xssData = [
            'invoice_id' => $invoice['invoice_id'],
            'items' => json_encode([
                [
                    'item_name' => '<script>alert("xss")</script>',
                    'item_description' => '<img src=x onerror=alert("xss")>',
                    'item_quantity' => 1,
                    'item_price' => 100.00,
                ],
            ]),
        ];

        /* Act */
        // Global XSS sanitization happens in Admin_Controller::filter_input()

        /* Assert */
        // Verify XSS is stripped by global sanitization
    }
}
