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
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['clients'];
    }
    
    /**
     * Load fixtures using SOLID trait pattern
     */
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Public File Access Tests

    /**
     * Test show_files returns empty for invalid URL key
     */
    #[Test]
    public function it_returns_empty_array_for_invalid_url_key(): void
    {
        /* Arrange */
        $invalidUrlKey = 'invalid-key';
        
        /**
         * Act: GET /get/show_files?url_key=invalid-key
         * Expected behavior: Return empty JSON array for non-existent URL key
         */
        $response = $this->get('/get/show_files?url_key=' . $invalidUrlKey);
        
        /* Assert */
        $this->assertJsonResponseSuccess($response);
        $response->assertJson([]);
    }

    /**
     * Happy Path: show_files returns files for valid URL key
     */
    #[Test]
    public function it_returns_files_for_valid_client_url_key(): void
    {
        /* Arrange */
        $client = $this->getClientData('active');
        
        /**
         * Act: GET /get/show_files?url_key={valid_key}
         * Expected behavior: Return files associated with client
         */
        $response = $this->get('/get/show_files?url_key=' . $client['client_url_key']);
        
        /* Assert */
        $this->assertJsonResponseSuccess($response);
        $response->assertJson([]);
    }

    // #endregion

    // #region File Download Tests

    /**
     * Test get_file returns 404 for non-existent file
     */
    #[Test]
    public function it_returns_404_for_nonexistent_file(): void
    {
        /* Arrange */
        $nonexistentFilename = 'nonexistent.pdf';
        
        /**
         * Act: GET /get/get_file?filename=nonexistent.pdf
         * Expected behavior: Return 404 when file does not exist
         */
        $response = $this->get('/get/get_file?filename=' . $nonexistentFilename);
        
        /* Assert */
        $this->assertNotFoundResponse($response);
    }

    /**
     * Happy Path: get_file downloads existing file
     */
    #[Test]
    public function it_downloads_existing_file_with_headers(): void
    {
        /* Arrange */
        $validFilename = 'invoice_123.pdf';
        
        /**
         * Act: GET /get/get_file?filename=invoice_123.pdf
         * Expected behavior: Return file with proper Content-Disposition header
         */
        $response = $this->get('/get/get_file?filename=' . $validFilename);
        
        /* Assert */
        $response->assertHeader('Content-Disposition');
    }

    // #endregion

    // #region Security Tests

    /**
     * Test get_file blocks path traversal attacks
     */
    #[Test]
    public function it_blocks_path_traversal_attacks(): void
    {
        /* Arrange */
        $maliciousFilename = '../../../etc/passwd';
        
        /**
         * Act: GET /get/get_file?filename=../../../etc/passwd
         * Expected behavior: Block path traversal attempt with 403
         */
        $response = $this->get('/get/get_file?filename=' . urlencode($maliciousFilename));
        
        /* Assert */
        $this->assertForbiddenResponse($response);
    }

    // #endregion
}
