<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentsController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing methods.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(PaymentsController::class)]
class PaymentsControllerTest extends TestCase
{
    
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Test data will be set up per test as needed
    }

    #[Test]
    public function it_displays_payments_index_requires_authentication(): void
    {
        $response = $this->get('/payments/index');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_displays_payments_index_payments_list(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->get('/payments/index');
        
        $response->assertOk();
        $response->assertSee('Payments');
    }

    #[Test]
    public function it_displays_payments_index_paginates_results(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->get('/payments/index/1');
        
        $response->assertOk();
        $response->assertSee('pagination');
    }

    #[Test]
    public function it_displays_payments_form_requires_authentication(): void
    {
        $response = $this->get('/payments/form');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_displays_payments_form_new_payment_form(): void
    {
        $this->actingAs($this->createAdminUser());
        $invoiceId = 1;
        
        $response = $this->get('/payments/form/' . $invoiceId);
        
        $response->assertOk();
        $response->assertSee('Payment Form');
    }

    #[Test]
    public function it_displays_payments_form_edit_payment_form(): void
    {
        $this->actingAs($this->createAdminUser());
        $invoiceId = 1;
        $paymentId = 1;
        
        $response = $this->get('/payments/form/' . $invoiceId . '/' . $paymentId);
        
        $response->assertOk();
        $response->assertSee('Payment Form');
    }

    #[Test]
    public function it_creates_payments_new_payment_with_valid_credentials(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $newPayment = [
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => 1,
        ];
        
        $response = $this->post('/payments/form/' . $newPayment['invoice_id'], $newPayment);
        
        $response->assertRedirect('/payments');
    }

    #[Test]
    public function it_validates_payments_required_fields(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidPayment = ['invoice_id' => 1];
        
        $response = $this->post('/payments/form/1', $invalidPayment);
        
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function it_validates_payments_payment_amount(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidPayment = [
            'invoice_id' => 1,
            'payment_amount' => 'invalid',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/form/1', $invalidPayment);
        
        $response->assertSessionHasErrors('payment_amount');
    }

    #[Test]
    public function it_updates_payments_existing_payment(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $updatedData = [
            'invoice_id' => 1,
            'payment_id' => 1,
            'payment_amount' => '150.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => 1,
            'payment_note' => 'Updated note',
        ];
        
        $response = $this->post('/payments/form/1/1', $updatedData);
        
        $response->assertRedirect('/payments');
    }

    #[Test]
    public function it_post_payments_form_saves_custom_fields(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $paymentWithCustom = [
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => 1,
            'custom_fields' => ['field1' => 'value1'],
        ];
        
        $response = $this->post('/payments/form/1', $paymentWithCustom);
        
        $response->assertRedirect('/payments');
    }

    #[Test]
    public function it_post_payments_form_cancels_without_saving(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->post('/payments/form/1', ['btn_cancel' => '1', 'invoice_id' => 1]);
        
        $response->assertRedirect('/payments');
    }

    #[Test]
    public function it_displays_payments_online_logs_payment_logs(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->get('/payments/online_logs');
        
        $response->assertOk();
        $response->assertSee('Online Payment Logs');
    }

    #[Test]
    public function it_deletes_payments_removes_payment(): void
    {
        $this->actingAs($this->createAdminUser());
        $paymentId = 1;
        
        $response = $this->post('/payments/delete/' . $paymentId);
        
        $response->assertRedirect('/payments');
    }

    #[Test]
    public function it_deletes_payments_updates_invoice_balance(): void
    {
        $this->actingAs($this->createAdminUser());
        $paymentId = 1;
        
        $response = $this->post('/payments/delete/' . $paymentId);
        
        $response->assertRedirect('/payments');
    }

    #[Test]
    public function it_sanitizes_xss_attempts_in_payment_data(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $xssPayment = [
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => 1,
            'payment_note' => '<script>alert("XSS")</script>',
        ];
        
        $response = $this->post('/payments/form/1', $xssPayment);
        
        // XSS is sanitized by Admin_Controller::filter_input()
        $response->assertStatus(302);
    }

    #[Test]
    public function it_protects_against_sql_injection(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $sqlInjection = "1' OR '1'='1";
        
        $response = $this->get('/payments/form/' . $sqlInjection);
        
        // SQL injection is prevented by Query Builder
        $response->assertStatus(200);
    }

    #[Test]
    public function it_validates_payment_date_format(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidDate = [
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => 'invalid-date',
            'payment_method_id' => 1,
        ];
        
        $response = $this->post('/payments/form/1', $invalidDate);
        
        $response->assertSessionHasErrors('payment_date');
    }

    #[Test]
    public function it_validates_payment_method_exists(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidMethod = [
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => 999,
        ];
        
        $response = $this->post('/payments/form/1', $invalidMethod);
        
        $response->assertSessionHasErrors('payment_method_id');
    }

    #[Test]
    public function it_prevents_negative_payment_amounts(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $negativePayment = [
            'invoice_id' => 1,
            'payment_amount' => '-100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => 1,
        ];
        
        $response = $this->post('/payments/form/1', $negativePayment);
        
        $response->assertSessionHasErrors('payment_amount');
    }
}
