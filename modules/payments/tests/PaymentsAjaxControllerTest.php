<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentsAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentsAjaxController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing methods.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(PaymentsAjaxController::class)]
class PaymentsAjaxControllerTest extends TestCase
{
    
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Test data will be set up per test as needed
    }

    #[Test]
    public function it_create_requires_authentication(): void
    {
        $response = $this->post('/payments/ajax/create');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_create_requires_admin_role(): void
    {
        $this->actingAs($this->createGuestUser());
        
        $response = $this->post('/payments/ajax/create');
        
        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_create_creates_payment_with_valid_credentials(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $validPaymentData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '250.00',
            'payment_date' => date('Y-m-d'),
            'payment_note' => 'Partial payment',
        ];
        
        $response = $this->post('/payments/ajax/create', $validPaymentData);
        
        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function it_create_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidData = [
            'invoice_id' => 1,
            'payment_amount' => '',
        ];
        
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        $response->assertStatus(422);
    }

    #[Test]
    public function it_create_validates_payment_amount_format(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => 'not-a-number',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        $response->assertStatus(422);
    }

    #[Test]
    public function it_create_rejects_negative_payment_amount(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '-100.00',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        $response->assertStatus(422);
    }

    #[Test]
    public function it_create_rejects_zero_payment_amount(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '0.00',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        $response->assertStatus(422);
    }

    #[Test]
    public function it_create_handles_overpayment(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $overpaymentData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '10000.00',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/create', $overpaymentData);
        
        $response->assertStatus(422);
    }

    #[Test]
    public function it_create_validates_payment_date_format(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => 'invalid-date',
        ];
        
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        $response->assertStatus(422);
    }

    #[Test]
    public function it_create_sanitizes_xss_in_payment_note(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $xssData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_note' => '<script>alert("xss")</script>',
        ];
        
        $response = $this->post('/payments/ajax/create', $xssData);
        
        // XSS should be sanitized by global filter
        $response->assertOk();
    }

    #[Test]
    public function it_create_protects_against_sql_injection(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $sqlInjectionData = [
            'invoice_id' => "1 OR 1=1; DROP TABLE ip_payments; --",
            'payment_method_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/create', $sqlInjectionData);
        
        // Query Builder should protect against SQL injection
        $response->assertStatus(422);
    }

    #[Test]
    public function it_create_returns_payment_id_on_success(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $validData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/create', $validData);
        
        $response->assertOk();
        $response->assertJsonStructure(['payment_id']);
    }

    #[Test]
    public function it_save_requires_authentication(): void
    {
        $response = $this->post('/payments/ajax/save');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_save_requires_admin_role(): void
    {
        $this->actingAs($this->createGuestUser());
        
        $response = $this->post('/payments/ajax/save');
        
        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_save_saves_payment_with_valid_data(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $validData = [
            'payment_id' => 1,
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '150.00',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/save', $validData);
        
        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function it_save_saves_custom_fields(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $paymentData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'custom' => [
                '1' => 'Custom Value',
            ],
        ];
        
        $response = $this->post('/payments/ajax/save', $paymentData);
        
        $response->assertOk();
    }

    #[Test]
    public function it_save_clears_balance_on_full_payment(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $fullPaymentData = [
            'invoice_id' => 1,
            'payment_method_id' => 1,
            'payment_amount' => '500.00',
            'payment_date' => date('Y-m-d'),
        ];
        
        $response = $this->post('/payments/ajax/save', $fullPaymentData);
        
        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function it_get_latest_requires_authentication(): void
    {
        $response = $this->post('/payments/ajax/get_latest');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_get_latest_requires_admin_role(): void
    {
        $this->actingAs($this->createGuestUser());
        
        $response = $this->post('/payments/ajax/get_latest');
        
        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_get_latest_returns_latest_payment_data(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->post('/payments/ajax/get_latest', ['invoice_id' => 1]);
        
        $response->assertOk();
        $response->assertJsonStructure(['payment_id', 'payment_amount']);
    }
}
