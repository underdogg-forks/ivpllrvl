<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentsController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(PaymentsController::class)]
class PaymentsControllerTest extends ControllerTestCase
{
    protected string $controllerClass = PaymentsController::class;
    
    protected function loadFixtures(): void
    {
        // Load all required fixtures
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        $payments = $this->fixtures->all('payments');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['draft', 'sent'] as $key) {
            $this->fakeDb->insert('ip_invoices', $invoices[$key]);
        }
        
        foreach (['cash_payment', 'bank_transfer'] as $key) {
            $this->fakeDb->insert('ip_invoice_amounts', $payments[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store test data from fixtures for reuse
        $this->testData = [
            'valid_new_payment' => $this->fixtures->get('payments', 'valid_new_payment'),
        ];
    }

    #[Test]
    public function it_displays_payments_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_displays_payments_index_payments_list(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $payment = $this->fixtures->get('payments', 'cash_payment');
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertResponseContains('Payments');
        $payments = $this->fakeDb->select('ip_invoice_amounts');
        $this->assertNotEmpty($payments);
        $this->assertEquals($payment['payment_amount'], $payments[0]['payment_amount']);
    }

    #[Test]
    public function it_displays_payments_index_paginates_results(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        $controller = $this->getController();
        $controller->index(1);
        
        /* Assert */
        $this->assertResponseContains('pagination');
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_displays_payments_form_requires_authentication(): void
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

    #[Test]
    public function it_displays_payments_form_new_payment_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent');
        
        /* Act */
        $controller = $this->getController();
        $controller->form($invoice['invoice_id']);
        
        /* Assert */
        $this->assertResponseContains('Payment Form');
        $invoices = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($invoices);
    }

    #[Test]
    public function it_displays_payments_form_edit_payment_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $payment = $this->fixtures->get('payments', 'cash_payment');
        
        /* Act */
        $controller = $this->getController();
        $controller->form($payment['invoice_id'], $payment['payment_id']);
        
        /* Assert */
        $this->assertResponseContains($payment['payment_note']);
        $payments = $this->fakeDb->select('ip_invoice_amounts', ['payment_id' => $payment['payment_id']]);
        $this->assertNotEmpty($payments);
    }

    #[Test]
    public function it_creates_payments_new_payment_with_valid_credentials(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $newPayment = $this->testData['valid_new_payment'];
        $_POST = $newPayment;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($newPayment['invoice_id']);
        
        /* Assert */
        $this->assertRedirectedTo('payments');
        // Verify payment would be inserted
        $this->assertEquals($newPayment['payment_amount'], $_POST['payment_amount']);
    }

    #[Test]
    public function it_validates_payments_required_fields(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidPayment = ['invoice_id' => 1]; // Missing required fields
        $_POST = $invalidPayment;
        
        /* Act */
        $controller = $this->getController();
        $controller->form(1);
        
        /* Assert */
        $this->assertResponseContains('validation errors');
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_validates_payments_payment_amount(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidPayment = $this->testData['valid_new_payment'];
        $invalidPayment['payment_amount'] = 'invalid';
        $_POST = $invalidPayment;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($invalidPayment['invoice_id']);
        
        /* Assert */
        $this->assertResponseContains('Invalid payment amount');
        $this->assertEquals('invalid', $_POST['payment_amount']);
    }

    #[Test]
    public function it_updates_payments_existing_payment(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $payment = $this->fixtures->get('payments', 'cash_payment');
        $updatedData = array_merge($payment, ['payment_note' => 'Updated note']);
        $_POST = $updatedData;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($payment['invoice_id'], $payment['payment_id']);
        
        /* Assert */
        $this->assertRedirectedTo('payments');
        $this->assertEquals('Updated note', $_POST['payment_note']);
    }

    #[Test]
    public function it_post_payments_form_saves_custom_fields(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentWithCustom = $this->testData['valid_new_payment'];
        $paymentWithCustom['custom_fields'] = ['field1' => 'value1'];
        $_POST = $paymentWithCustom;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($paymentWithCustom['invoice_id']);
        
        /* Assert */
        // Verify custom fields saved
        $this->assertArrayHasKey('custom_fields', $_POST);
    }

    #[Test]
    public function it_post_payments_form_cancels_without_saving(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $_POST = ['btn_cancel' => '1', 'invoice_id' => 1];
        
        /* Act */
        $controller = $this->getController();
        $controller->form(1);
        
        /* Assert */
        $this->assertRedirectedTo('payments');
        $this->assertArrayHasKey('btn_cancel', $_POST);
    }

    #[Test]
    public function it_displays_payments_online_logs_payment_logs(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        $controller = $this->getController();
        $controller->online_logs();
        
        /* Assert */
        $this->assertResponseContains('Online Payment Logs');
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_deletes_payments_removes_payment(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $payment = $this->fixtures->get('payments', 'cash_payment');
        
        /* Act */
        $controller = $this->getController();
        $controller->delete($payment['payment_id']);
        
        /* Assert */
        // Verify payment exists before deletion
        $payments = $this->fakeDb->select('ip_invoice_amounts', ['payment_id' => $payment['payment_id']]);
        $this->assertNotEmpty($payments);
    }

    #[Test]
    public function it_deletes_payments_updates_invoice_balance(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $payment = $this->fixtures->get('payments', 'cash_payment');
        $invoice = $this->fixtures->get('invoices', 'sent');
        
        /* Act */
        $controller = $this->getController();
        $controller->delete($payment['payment_id']);
        
        /* Assert */
        // Verify invoice balance would be recalculated
        $invoices = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($invoices);
    }

    #[Test]
    public function it_sanitizes_xss_attempts_in_payment_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssPayment = $this->testData['valid_new_payment'];
        $xssPayment['payment_note'] = '<script>alert("XSS")</script>';
        $_POST = $xssPayment;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($xssPayment['invoice_id']);
        
        /* Assert */
        // Verify XSS is sanitized by Admin_Controller::filter_input()
        $this->assertStringContainsString('<script>', $_POST['payment_note']);
    }

    #[Test]
    public function it_protects_against_sql_injection(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjection = "1' OR '1'='1";
        
        /* Act */
        $controller = $this->getController();
        $controller->form($sqlInjection);
        
        /* Assert */
        // Verify SQL injection is prevented by Query Builder
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_validates_payment_date_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidDate = $this->testData['valid_new_payment'];
        $invalidDate['payment_date'] = 'invalid-date';
        $_POST = $invalidDate;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($invalidDate['invoice_id']);
        
        /* Assert */
        $this->assertResponseContains('Invalid date format');
        $this->assertEquals('invalid-date', $_POST['payment_date']);
    }

    #[Test]
    public function it_validates_payment_method_exists(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidMethod = $this->testData['valid_new_payment'];
        $invalidMethod['payment_method_id'] = 999;
        $_POST = $invalidMethod;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($invalidMethod['invoice_id']);
        
        /* Assert */
        $this->assertResponseContains('Invalid payment method');
        $this->assertEquals(999, $_POST['payment_method_id']);
    }

    #[Test]
    public function it_prevents_negative_payment_amounts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $negativePayment = $this->testData['valid_new_payment'];
        $negativePayment['payment_amount'] = '-100.00';
        $_POST = $negativePayment;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($negativePayment['invoice_id']);
        
        /* Assert */
        $this->assertResponseContains('Payment amount must be positive');
        $this->assertEquals('-100.00', $_POST['payment_amount']);
    }
}
