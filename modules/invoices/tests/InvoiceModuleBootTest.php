<?php

namespace Modules\Invoices\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Testing\TestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for Invoice Module Boot
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ModuleResourceRegistry::class)]
class InvoiceModuleBootTest extends TestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users'];
    }
    
    /**
     * Load fixtures using SOLID trait pattern
     */
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }

    // #region Module Boot Tests

    /**
     * Test that invoice module boots successfully
     */
    #[Test]
    public function it_boots_invoice_module_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: Access any invoice route to trigger module boot
         * Expected behavior: Module loads without errors
         */
        $response = $this->get('/invoices/index');
        
        /* Assert */
        $this->assertTrue($this->fakeSession->has('user_id'));
        $response->assertStatus([200, 302]); // Either OK or redirect
    }

    // #endregion
}
