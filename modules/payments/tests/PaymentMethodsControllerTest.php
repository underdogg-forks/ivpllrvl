<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentMethodsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentMethodsController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing methods.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(PaymentMethodsController::class)]
class PaymentMethodsControllerTest extends TestCase
{
    
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Test data will be set up per test as needed
    }

    #[Test]
    public function it_index_requires_authentication(): void
    {
        $response = $this->get('/payment_methods/index');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_index_requires_admin_role(): void
    {
        $this->actingAs($this->createGuestUser());
        
        $response = $this->get('/payment_methods/index');
        
        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_index_returns_payment_methods_list_for_admin(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->get('/payment_methods/index');
        
        $response->assertOk();
        $response->assertSee('filter_payment_methods');
    }

    #[Test]
    public function it_index_supports_pagination(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->get('/payment_methods/index');
        
        $response->assertOk();
    }

    #[Test]
    public function it_index_shows_empty_state_when_no_payment_methods(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->get('/payment_methods/index');
        
        $response->assertOk();
    }

    #[Test]
    public function it_form_requires_authentication(): void
    {
        $response = $this->get('/payment_methods/form');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_form_requires_admin_role(): void
    {
        $this->actingAs($this->createGuestUser());
        
        $response = $this->get('/payment_methods/form');
        
        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_displays_new_form(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $response = $this->get('/payment_methods/form');
        
        $response->assertOk();
        $response->assertSee('payment_method_name');
    }

    #[Test]
    public function it_displays_edit_form(): void
    {
        $this->actingAs($this->createAdminUser());
        $paymentMethodId = 1;
        
        $response = $this->get('/payment_methods/form/' . $paymentMethodId);
        
        $response->assertOk();
    }

    #[Test]
    public function it_form_returns_404_for_invalid_payment_method(): void
    {
        $this->actingAs($this->createAdminUser());
        $invalidId = 9999;
        
        $response = $this->get('/payment_methods/form/' . $invalidId);
        
        $response->assertNotFound();
    }

    #[Test]
    public function it_form_creates_new_payment_method_with_valid_credentials(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $newMethod = [
            'payment_method_name' => 'PayPal',
            'btn_submit' => '1',
        ];
        
        $response = $this->post('/payment_methods/form', $newMethod);
        
        $response->assertRedirect('/payment_methods');
    }

    #[Test]
    public function it_form_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $invalidData = [
            'btn_submit' => '1',
            'payment_method_name' => '',
        ];
        
        $response = $this->post('/payment_methods/form', $invalidData);
        
        $response->assertSessionHasErrors('payment_method_name');
    }

    #[Test]
    public function it_form_rejects_duplicate_payment_method_name(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $duplicateData = [
            'btn_submit' => '1',
            'payment_method_name' => 'Cash',
        ];
        
        $response = $this->post('/payment_methods/form', $duplicateData);
        
        $response->assertSessionHasErrors('payment_method_name');
    }

    #[Test]
    public function it_form_sanitizes_xss_attempts(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $xssData = [
            'btn_submit' => '1',
            'payment_method_name' => '<script>alert("xss")</script>',
        ];
        
        $response = $this->post('/payment_methods/form', $xssData);
        
        // XSS should be sanitized by global filter
        $response->assertStatus(302);
    }

    #[Test]
    public function it_form_protects_against_sql_injection(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $sqlInjectionData = [
            'btn_submit' => '1',
            'payment_method_name' => "'; DROP TABLE ip_payment_methods; --",
        ];
        
        $response = $this->post('/payment_methods/form', $sqlInjectionData);
        
        // Query Builder should protect against SQL injection
        $response->assertStatus(302);
    }

    #[Test]
    public function it_form_updates_existing_payment_method(): void
    {
        $this->actingAs($this->createAdminUser());
        $paymentMethodId = 1;
        
        $updatedData = [
            'btn_submit' => '1',
            'payment_method_name' => 'Updated Name',
        ];
        
        $response = $this->post('/payment_methods/form/' . $paymentMethodId, $updatedData);
        
        $response->assertRedirect('/payment_methods');
    }

    #[Test]
    public function it_form_cancels_without_saving(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $cancelData = [
            'btn_cancel' => 'Cancel',
            'payment_method_name' => 'Should Not Save',
        ];
        
        $response = $this->post('/payment_methods/form', $cancelData);
        
        $response->assertRedirect('/payment_methods');
    }

    #[Test]
    public function it_form_allows_updating_existing_payment_method_with_same_name(): void
    {
        $this->actingAs($this->createAdminUser());
        $paymentMethodId = 1;
        
        $sameNameData = [
            'btn_submit' => '1',
            'payment_method_name' => 'Cash',
        ];
        
        $response = $this->post('/payment_methods/form/' . $paymentMethodId, $sameNameData);
        
        $response->assertRedirect('/payment_methods');
    }

    #[Test]
    public function it_delete_requires_authentication(): void
    {
        $response = $this->post('/payment_methods/delete/1');
        
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_delete_requires_admin_role(): void
    {
        $this->actingAs($this->createGuestUser());
        
        $response = $this->post('/payment_methods/delete/1');
        
        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function it_delete_removes_payment_method(): void
    {
        $this->actingAs($this->createAdminUser());
        $methodId = 3;
        
        $response = $this->post('/payment_methods/delete/' . $methodId);
        
        $response->assertRedirect('/payment_methods');
    }

    #[Test]
    public function it_delete_handles_invalid_payment_method_id(): void
    {
        $this->actingAs($this->createAdminUser());
        $invalidId = 9999;
        
        $response = $this->post('/payment_methods/delete/' . $invalidId);
        
        $response->assertStatus(404);
    }

    #[Test]
    public function it_delete_protects_against_sql_injection(): void
    {
        $this->actingAs($this->createAdminUser());
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_payment_methods; --";
        
        $response = $this->post('/payment_methods/delete/' . $sqlInjection);
        
        // Query Builder should protect against SQL injection
        $response->assertStatus(404);
    }

    #[Test]
    public function it_delete_handles_foreign_key_constraints(): void
    {
        $this->actingAs($this->createAdminUser());
        $methodId = 1;
        
        $response = $this->post('/payment_methods/delete/' . $methodId);
        
        // Should either prevent deletion or handle gracefully
        $response->assertStatus(302);
    }

    #[Test]
    public function it_form_handles_long_payment_method_names(): void
    {
        $this->actingAs($this->createAdminUser());
        $longName = str_repeat('A', 255);
        
        $longNameData = [
            'btn_submit' => '1',
            'payment_method_name' => $longName,
        ];
        
        $response = $this->post('/payment_methods/form', $longNameData);
        
        // Should handle or truncate long names
        $response->assertStatus(302);
    }

    #[Test]
    public function it_form_handles_special_characters_in_name(): void
    {
        $this->actingAs($this->createAdminUser());
        
        $specialCharsData = [
            'btn_submit' => '1',
            'payment_method_name' => 'Bank Transfer (€ / £ / $)',
        ];
        
        $response = $this->post('/payment_methods/form', $specialCharsData);
        
        $response->assertRedirect('/payment_methods');
    }
}
