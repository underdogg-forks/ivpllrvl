<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\ViewController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ViewController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ViewController::class)]
class ViewControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ViewController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'quotes'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
    }

    // #region Invoice View Tests

    #[Test]
    public function it_returns_404_for_invalid_invoice_url_key(): void
    {
        /* Arrange */
        $invalidUrlKey = 'invalid-key-12345';
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected behavior: Return 404 for invalid URL key
         */
        $response = $this->get('/guest/view/' . $invalidUrlKey);
        
        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_invoices', ['invoice_url_key' => $invalidUrlKey]);
    }

    // #endregion
}
