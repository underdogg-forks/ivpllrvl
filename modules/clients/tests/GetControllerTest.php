<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\GetController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for GetController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(GetController::class)]
class GetControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = GetController::class;
    
    protected function fixtureTypes(): array
    {
        return ['clients'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
    }

    // #region File Display Tests

    #[Test]
    public function it_returns_empty_for_invalid_url_key(): void
    {
        /* Arrange */
        $invalidKey = 'invalid-key';
        
        /**
         * Act: GET /get/show_files?url_key=invalid-key
         * Expected behavior: Return empty JSON array
         */
        $response = $this->get('/get/show_files?url_key=' . $invalidKey);
        
        /* Assert */
        $response->assertJson([]);
    }

    // #endregion

    // #region Security Tests

    #[Test]
    public function it_blocks_path_traversal_attacks_in_file_download(): void
    {
        /* Arrange */
        $maliciousFilename = '../../../etc/passwd';
        
        /**
         * Act: GET /get/get_file?filename=../../../etc/passwd
         * Expected behavior: Return 403 Forbidden
         */
        $response = $this->get('/get/get_file?filename=' . urlencode($maliciousFilename));
        
        /* Assert */
        $response->assertForbidden();
    }

    // #endregion
}
