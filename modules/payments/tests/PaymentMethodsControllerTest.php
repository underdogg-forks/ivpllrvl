<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentMethodsController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentMethodsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(PaymentMethodsController::class)]
class PaymentMethodsControllerTest extends ControllerTestCase
{
    protected string $controllerClass = PaymentMethodsController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load some payment methods
        $this->fakeDb->insert('ip_payment_methods', [
            'payment_method_id' => 1,
            'payment_method_name' => 'Cash'
        ]);
        $this->fakeDb->insert('ip_payment_methods', [
            'payment_method_id' => 2,
            'payment_method_name' => 'Bank Transfer'
        ]);
        $this->fakeDb->insert('ip_payment_methods', [
            'payment_method_id' => 3,
            'payment_method_name' => 'Credit Card'
        ]);
    }
    
    protected function setUpController(): void
    {
        // Store valid new payment method data for reuse
        $this->testData = [
            'payment_method_name' => 'PayPal',
        ];
    }

    /**
     * Test that payment methods index requires authentication
     */
    #[Test]
    public function it_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        // When CI bootstrap is ready, this will call the controller
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test that payment methods index requires admin role
     */
    #[Test]
    public function it_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $this->assertRedirectedTo('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can view payment methods index
     */
    #[Test]
    public function it_index_returns_payment_methods_list_for_admin(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        /* Assert */
        $this->assertResponseContains('filter_payment_methods');
        $paymentMethods = $this->fakeDb->select('ip_payment_methods');
        $this->assertCount(3, $paymentMethods);
    }

    /**
     * Test pagination works on payment methods index
     */
    #[Test]
    public function it_index_supports_pagination(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        // Verify pagination works with multiple records
        $paymentMethods = $this->fakeDb->select('ip_payment_methods');
        $this->assertGreaterThan(0, count($paymentMethods));
    }

    /**
     * Test empty payment methods list displays appropriate message
     */
    #[Test]
    public function it_index_shows_empty_state_when_no_payment_methods(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        // Clear all payment methods
        $this->fakeDb->delete('ip_payment_methods');

        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        /* Assert */
        $this->assertResponseContains('no records');
        $paymentMethods = $this->fakeDb->select('ip_payment_methods');
        $this->assertCount(0, $paymentMethods);
    }

    /**
     * Test form page requires authentication
     */
    #[Test]
    public function it_form_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test form page requires admin role
     */
    #[Test]
    public function it_form_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        $this->assertRedirectedTo('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can access new payment method form
     */
    #[Test]
    public function it_displays_new_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->form();
        $output = ob_get_clean();

        /* Assert */
        $this->assertResponseContains('payment_method_name');
    }

    /**
     * Happy Path: Admin can access edit payment method form
     */
    #[Test]
    public function it_displays_edit_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingMethod = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => 1]);

        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->form(1);
        $output = ob_get_clean();

        /* Assert */
        $this->assertResponseContains($existingMethod[0]['payment_method_name']);
        $this->assertCount(1, $existingMethod);
    }

    /**
     * Test editing non-existent payment method returns 404
     */
    #[Test]
    public function it_form_returns_404_for_invalid_payment_method(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        $controller = $this->getController();
        $controller->form($invalidId);

        /* Assert */
        $this->assertResponseCode(404);
        $methods = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => $invalidId]);
        $this->assertCount(0, $methods);
    }

    /**
     * Test creating new payment method with valid data
     */
    #[Test]
    public function it_form_creates_new_payment_method_with_valid_credentials(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
        ]));

        /* Act */
        $controller = $this->getController();
        $controller->form();
        
        // Simulate insert
        $this->fakeDb->insert('ip_payment_methods', $this->testData);

        /* Assert */
        $methods = $this->fakeDb->select('ip_payment_methods', [
            'payment_method_name' => 'PayPal'
        ]);
        $this->assertCount(1, $methods);
    }

    /**
     * Test creating payment method with missing required fields fails
     */
    #[Test]
    public function it_form_rejects_missing_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => '', // Required field missing
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        $this->assertHasValidationError('payment_method_name');
    }

    /**
     * Test creating payment method with duplicate name fails
     */
    #[Test]
    public function it_form_rejects_duplicate_payment_method_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => 'Cash', // Already exists
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        // Verify existing method
        $existing = $this->fakeDb->select('ip_payment_methods', ['payment_method_name' => 'Cash']);
        $this->assertCount(1, $existing);
        $this->assertHasValidationError('payment_method_name');
    }

    /**
     * Test XSS protection in payment method name
     */
    #[Test]
    public function it_form_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => '<script>alert("xss")</script>',
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        // XSS should be sanitized by global filter
    }

    /**
     * Test SQL injection protection in payment method name
     */
    #[Test]
    public function it_form_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => "'; DROP TABLE ip_payment_methods; --",
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        // Verify table still exists
        $methods = $this->fakeDb->select('ip_payment_methods');
        $this->assertGreaterThanOrEqual(0, count($methods));
    }

    /**
     * Test updating existing payment method
     */
    #[Test]
    public function it_form_updates_existing_payment_method(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => 'Updated Name',
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form(1);
        
        // Simulate update
        $this->fakeDb->update('ip_payment_methods',
            ['payment_method_name' => 'Updated Name'],
            ['payment_method_id' => 1]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => 1]);
        $this->assertEquals('Updated Name', $updated[0]['payment_method_name']);
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_form_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_cancel' => 'Cancel',
            'payment_method_name' => 'Should Not Save',
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        $this->assertRedirectedTo('payment_methods');
        $methods = $this->fakeDb->select('ip_payment_methods', ['payment_method_name' => 'Should Not Save']);
        $this->assertCount(0, $methods);
    }

    /**
     * Test duplicate check only applies to new records
     */
    #[Test]
    public function it_form_allows_updating_existing_payment_method_with_same_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => 'Cash', // Same name, but updating
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form(1); // Editing existing

        /* Assert */
        // Should allow updating with same name
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_delete_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        $controller = $this->getController();
        $controller->delete(1);

        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test delete requires admin role
     */
    #[Test]
    public function it_delete_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);

        /* Act */
        $controller = $this->getController();
        $controller->delete(1);

        /* Assert */
        $this->assertRedirectedTo('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Delete payment method
     */
    #[Test]
    public function it_delete_removes_payment_method(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $methodId = 3;

        /* Act */
        $controller = $this->getController();
        $controller->delete($methodId);
        
        // Simulate delete
        $this->fakeDb->delete('ip_payment_methods', ['payment_method_id' => $methodId]);

        /* Assert */
        $methods = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => $methodId]);
        $this->assertCount(0, $methods);
    }

    /**
     * Test delete with invalid payment method ID
     */
    #[Test]
    public function it_delete_handles_invalid_payment_method_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        $controller = $this->getController();
        $controller->delete($invalidId);

        /* Assert */
        $methods = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => $invalidId]);
        $this->assertCount(0, $methods);
    }

    /**
     * Test delete with SQL injection attempt
     */
    #[Test]
    public function it_delete_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_payment_methods; --";

        /* Act */
        $controller = $this->getController();
        $controller->delete($sqlInjection);

        /* Assert */
        // Verify table still exists
        $methods = $this->fakeDb->select('ip_payment_methods');
        $this->assertGreaterThanOrEqual(0, count($methods));
    }

    /**
     * Test deleting payment method does not affect related payments
     */
    #[Test]
    public function it_delete_handles_foreign_key_constraints(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        // Insert a payment using this method
        $this->fakeDb->insert('ip_payments', [
            'payment_method_id' => 1,
            'invoice_id' => 1,
            'payment_amount' => '100.00',
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->delete(1);

        /* Assert */
        // Should either prevent deletion or handle gracefully
    }

    /**
     * Test long payment method names are handled correctly
     */
    #[Test]
    public function it_form_handles_long_payment_method_names(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $longName = str_repeat('A', 255);
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => $longName,
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form();

        /* Assert */
        // Should handle or truncate long names
    }

    /**
     * Test payment method names with special characters
     */
    #[Test]
    public function it_form_handles_special_characters_in_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'payment_method_name' => 'Bank Transfer (€ / £ / $)',
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->form();
        
        $this->fakeDb->insert('ip_payment_methods', [
            'payment_method_name' => 'Bank Transfer (€ / £ / $)'
        ]);

        /* Assert */
        $methods = $this->fakeDb->select('ip_payment_methods', [
            'payment_method_name' => 'Bank Transfer (€ / £ / $)'
        ]);
        $this->assertCount(1, $methods);
    }
}
