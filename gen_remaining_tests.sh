#!/bin/bash

# Generate InvoicesAjaxControllerTest.php
cat > modules/invoices/tests/InvoicesAjaxControllerTest.php << 'EOF'
<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\InvoicesAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InvoicesAjaxController::class)]
class InvoicesAjaxControllerTest extends TestCase
{
    /**
     * Test that AJAX save requires authentication
     */
    #[Test]
    public function it_post_save_requires_authentication(): void
    {
        // Arrange - No authenticated user
        $saveData = [
            'invoice_id' => 1,
            'items' => json_encode([]),
        ];

        // Act
        // $response = $this->post('invoices/ajax/save', $saveData);

        // Assert
        // $this->assertRedirect($response, 'sessions/login');

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Happy Path: Save invoice with valid data
     */
    #[Test]
    public function it_post_save_updates_invoice_with_valid_data(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $client = $this->createClient();
        // $invoice = $this->createInvoice(['client_id' => $client->client_id]);

        $saveData = [
            'invoice_id' => 1, // $invoice->invoice_id
            'invoice_status_id' => 2, // Sent
            'invoice_date_created' => date('m/d/Y'),
            'invoice_date_due' => date('m/d/Y', strtotime('+30 days')),
            'invoice_password' => '',
            'invoice_terms' => 'Payment due within 30 days',
            'payment_method' => '1',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'items' => json_encode([
                [
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
                ]
            ]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $saveData);

        // Assert
        // $this->assertOk($response);
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // $this->assertDatabaseHas('ip_invoice_items', [
        //     'invoice_id' => $invoice->invoice_id,
        //     'item_name' => 'Test Item',
        // ]);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save validates invoice_id required
     */
    #[Test]
    public function it_post_save_validates_invoice_id_required(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();

        $invalidData = [
            'invoice_id' => '', // Missing
            'items' => json_encode([]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $invalidData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(0, $json['success']);
        // $this->assertArrayHasKey('validation_errors', $json);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save sanitizes XSS in invoice data
     */
    #[Test]
    public function it_post_save_sanitizes_xss_attempts(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $xssData = [
            'invoice_id' => 1,
            'invoice_password' => '<script>alert("xss")</script>',
            'invoice_terms' => '<img src=x onerror=alert("xss")>',
            'items' => json_encode([]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $xssData);

        // Assert
        // XSS should be stripped by xss_clean()
        // $this->assertDatabaseMissing('ip_invoices', [
        //     'invoice_password' => '<script>alert("xss")</script>',
        // ]);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save validates invoice_number format
     */
    #[Test]
    public function it_post_save_validates_invoice_number_format(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $invalidData = [
            'invoice_id' => 1,
            'invoice_number' => '../../../etc/passwd', // Invalid characters
            'invoice_status_id' => 2,
            'items' => json_encode([]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $invalidData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(0, $json['success']);
        // $this->assertStringContainsString('invalid_characters', $json['validation_errors']['invoice_number']);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save generates invoice_number when status changes from draft
     */
    #[Test]
    public function it_post_save_generates_invoice_number_on_status_change(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice([
        //     'invoice_status_id' => 1, // Draft
        //     'invoice_number' => '', // Empty
        // ]);

        $saveData = [
            'invoice_id' => 1,
            'invoice_number' => '', // Empty
            'invoice_status_id' => 2, // Change to Sent
            'items' => json_encode([]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $saveData);

        // Assert
        // Invoice number should be auto-generated
        // $updatedInvoice = $this->getInvoice($invoice->invoice_id);
        // $this->assertNotEmpty($updatedInvoice->invoice_number);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save handles items with quantity and price
     */
    #[Test]
    public function it_post_save_creates_invoice_items(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $saveData = [
            'invoice_id' => 1,
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
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $saveData);

        // Assert
        // Both items should be created
        // $items = $this->getInvoiceItems($invoice->invoice_id);
        // $this->assertCount(2, $items);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save rejects items without name when quantity/price provided
     */
    #[Test]
    public function it_post_save_rejects_items_without_name(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $invalidData = [
            'invoice_id' => 1,
            'items' => json_encode([
                [
                    'item_id' => null,
                    'item_name' => '', // Missing name
                    'item_quantity' => 2,
                    'item_price' => 50.00,
                ],
            ]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $invalidData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(0, $json['success']);
        // $this->assertArrayHasKey('item_name', $json['validation_errors']);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save handles global discount percent
     */
    #[Test]
    public function it_post_save_applies_global_discount_percent(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $saveData = [
            'invoice_id' => 1,
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
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $saveData);

        // Assert
        // Discount should be applied
        // $updatedInvoice = $this->getInvoice($invoice->invoice_id);
        // $this->assertEquals(10.00, $updatedInvoice->invoice_discount_percent);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save prevents both percent and amount discounts
     */
    #[Test]
    public function it_post_save_prevents_dual_discounts(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $invalidData = [
            'invoice_id' => 1,
            'invoice_discount_percent' => 10.00, // Both set
            'invoice_discount_amount' => 20.00,  // Should be rejected
            'items' => json_encode([]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $invalidData);

        // Assert
        // Amount should be zeroed out (percent takes precedence)
        // $updatedInvoice = $this->getInvoice($invoice->invoice_id);
        // $this->assertEquals(10.00, $updatedInvoice->invoice_discount_percent);
        // $this->assertEquals(0, $updatedInvoice->invoice_discount_amount);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save marks task as invoiced
     */
    #[Test]
    public function it_post_save_marks_task_as_invoiced(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();
        // $task = $this->createTask(['task_status' => 3]); // Complete

        $saveData = [
            'invoice_id' => 1,
            'items' => json_encode([
                [
                    'item_id' => null,
                    'item_name' => 'Task Item',
                    'item_task_id' => 1, // $task->task_id
                    'item_quantity' => 1,
                    'item_price' => 100.00,
                ],
            ]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $saveData);

        // Assert
        // Task should be marked as invoiced (status 4)
        // $updatedTask = $this->getTask($task->task_id);
        // $this->assertEquals(4, $updatedTask->task_status);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save_invoice_tax_rate adds global tax
     */
    #[Test]
    public function it_post_save_invoice_tax_rate_adds_global_tax(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();
        // $taxRate = $this->createTaxRate(['tax_rate_percent' => 10.00]);

        $taxData = [
            'invoice_id' => 1,
            'tax_rate_id' => 1,
            'include_item_tax' => 0,
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save_invoice_tax_rate', $taxData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // Only for legacy_calculation mode

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test delete_item removes invoice item
     */
    #[Test]
    public function it_post_delete_item_removes_item(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();
        // $item = $this->createInvoiceItem(['invoice_id' => $invoice->invoice_id]);

        $deleteData = [
            'item_id' => 1, // $item->item_id
        ];

        // Act
        // $response = $this->postJson("invoices/ajax/delete_item/{$invoice->invoice_id}", $deleteData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // $this->assertDatabaseMissing('ip_invoice_items', ['item_id' => $item->item_id]);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test delete_item marks task as complete (not invoiced)
     */
    #[Test]
    public function it_post_delete_item_reverts_task_status(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();
        // $task = $this->createTask(['task_status' => 4]); // Invoiced
        // $item = $this->createInvoiceItem([
        //     'invoice_id' => $invoice->invoice_id,
        //     'item_task_id' => $task->task_id,
        // ]);

        $deleteData = ['item_id' => 1];

        // Act
        // $response = $this->postJson("invoices/ajax/delete_item/{$invoice->invoice_id}", $deleteData);

        // Assert
        // Task should revert to complete (status 3)
        // $updatedTask = $this->getTask($task->task_id);
        // $this->assertEquals(3, $updatedTask->task_status);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test get_item returns item data
     */
    #[Test]
    public function it_post_get_item_returns_item_data(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();
        // $item = $this->createInvoiceItem([
        //     'invoice_id' => $invoice->invoice_id,
        //     'item_name' => 'Test Item',
        //     'item_quantity' => 2,
        //     'item_price' => 50.00,
        // ]);

        $getData = ['item_id' => 1];

        // Act
        // $response = $this->postJson('invoices/ajax/get_item', $getData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals('Test Item', $json['item_name']);
        // $this->assertEquals(2, $json['item_quantity']);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test copy_invoice duplicates invoice
     */
    #[Test]
    public function it_post_copy_invoice_creates_duplicate(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $client = $this->createClient();
        // $sourceInvoice = $this->createInvoice(['client_id' => $client->client_id]);
        // $this->createInvoiceItem([
        //     'invoice_id' => $sourceInvoice->invoice_id,
        //     'item_name' => 'Original Item',
        // ]);

        $copyData = [
            'invoice_id' => 1,
            'client_id' => $client->client_id,
            'invoice_date_created' => date('m/d/Y'),
            'invoice_date_due' => date('m/d/Y', strtotime('+30 days')),
            'invoice_group_id' => 1,
        ];

        // Act
        // $initialCount = $this->getDatabaseCount('ip_invoices');
        // $response = $this->postJson('invoices/ajax/copy_invoice', $copyData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // $this->assertEquals($initialCount + 1, $this->getDatabaseCount('ip_invoices'));
        // Items should be copied too

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test change_user updates invoice user
     */
    #[Test]
    public function it_post_change_user_updates_invoice_user(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $user1 = $this->createUser();
        // $user2 = $this->createUser();
        // $invoice = $this->createInvoice(['user_id' => $user1->user_id]);

        $changeData = [
            'invoice_id' => 1,
            'user_id' => 2, // $user2->user_id
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/change_user', $changeData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // $this->assertDatabaseHas('ip_invoices', [
        //     'invoice_id' => $invoice->invoice_id,
        //     'user_id' => $user2->user_id,
        // ]);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test change_client updates invoice client
     */
    #[Test]
    public function it_post_change_client_updates_invoice_client(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $client1 = $this->createClient();
        // $client2 = $this->createClient();
        // $invoice = $this->createInvoice(['client_id' => $client1->client_id]);

        $changeData = [
            'invoice_id' => 1,
            'client_id' => 2, // $client2->client_id
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/change_client', $changeData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // $this->assertDatabaseHas('ip_invoices', [
        //     'invoice_id' => $invoice->invoice_id,
        //     'client_id' => $client2->client_id,
        // ]);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test create creates new invoice
     */
    #[Test]
    public function it_post_create_creates_new_invoice(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $client = $this->createClient();

        $createData = [
            'client_id' => 1,
            'invoice_date_created' => date('m/d/Y'),
            'invoice_date_due' => date('m/d/Y', strtotime('+30 days')),
            'invoice_group_id' => 1,
        ];

        // Act
        // $initialCount = $this->getDatabaseCount('ip_invoices');
        // $response = $this->postJson('invoices/ajax/create', $createData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // $this->assertArrayHasKey('invoice_id', $json);
        // $this->assertEquals($initialCount + 1, $this->getDatabaseCount('ip_invoices'));

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test create_recurring creates recurring invoice
     */
    #[Test]
    public function it_post_create_recurring_creates_recurring_invoice(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $recurringData = [
            'invoice_id' => 1,
            'recur_start_date' => date('m/d/Y'),
            'recur_end_date' => date('m/d/Y', strtotime('+1 year')),
            'recur_frequency' => 'M', // Monthly
        ];

        // Act
        // $initialCount = $this->getDatabaseCount('ip_invoices_recurring');
        // $response = $this->postJson('invoices/ajax/create_recurring', $recurringData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // $this->assertEquals($initialCount + 1, $this->getDatabaseCount('ip_invoices_recurring'));

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test create_credit creates credit invoice
     */
    #[Test]
    public function it_post_create_credit_creates_credit_invoice(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $sourceInvoice = $this->createInvoice();

        $creditData = [
            'invoice_id' => 1,
            'invoice_date_created' => date('m/d/Y'),
            'invoice_group_id' => 1,
        ];

        // Act
        // $initialCount = $this->getDatabaseCount('ip_invoices');
        // $response = $this->postJson('invoices/ajax/create_credit', $creditData);

        // Assert
        // $json = json_decode($response->getBody(), true);
        // $this->assertEquals(1, $json['success']);
        // Credit invoice should be created with negative amounts
        // Source invoice should be marked read-only

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test custom fields are saved
     */
    #[Test]
    public function it_post_save_saves_custom_fields(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();
        // $customField = $this->createCustomField(['table' => 'ip_invoices']);

        $saveData = [
            'invoice_id' => 1,
            'items' => json_encode([]),
            'custom' => [
                ['name' => 'custom[1]', 'value' => 'Custom Value 1'],
            ],
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $saveData);

        // Assert
        // $this->assertDatabaseHas('ip_invoice_custom', [
        //     'invoice_id' => $invoice->invoice_id,
        //     'invoice_custom_fieldvalue' => 'Custom Value 1',
        // ]);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save respects einvoicing calculation mode
     */
    #[Test]
    public function it_post_save_respects_einvoicing_mode(): void
    {
        // Arrange
        // Set setting: einvoicing = 1
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $saveData = [
            'invoice_id' => 1,
            'legacy_calculation' => 0, // Use new calculation
            'items' => json_encode([]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $saveData);

        // Assert
        // Should use einvoicing calculation mode
        // $this->assertEquals(1, $json['success']);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    /**
     * Test save sanitizes input in XSS protection
     */
    #[Test]
    public function it_post_save_protects_against_xss_in_items(): void
    {
        // Arrange
        // $adminUserId = $this->actingAsAdmin();
        // $invoice = $this->createInvoice();

        $xssData = [
            'invoice_id' => 1,
            'items' => json_encode([
                [
                    'item_name' => '<script>alert("xss")</script>',
                    'item_description' => '<img src=x onerror=alert("xss")>',
                    'item_quantity' => 1,
                    'item_price' => 100.00,
                ],
            ]),
        ];

        // Act
        // $response = $this->postJson('invoices/ajax/save', $xssData);

        // Assert
        // XSS should be sanitized
        // $this->assertDatabaseMissing('ip_invoice_items', [
        //     'item_name' => '<script>alert("xss")</script>',
        // ]);

        $this->markTestIncomplete('HTTP test infrastructure needed');
    }
}
EOF

echo "InvoicesAjaxControllerTest.php created"
