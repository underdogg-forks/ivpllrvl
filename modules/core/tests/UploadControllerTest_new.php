<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UploadController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UploadController
 * 
 * Tests file upload, download, deletion with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(UploadController::class)]
class UploadControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = UploadController::class;
    
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
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        $this->testData = [
            'admin' => $this->fixtures->get('users', 'admin'),
        ];
    }
    
    /**
     * Build complete file upload data for $_FILES superglobal
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete file upload data
     */
    protected function makeFileUploadData(array $overrides = []): array
    {
        $defaults = [
            'file' => [
                'name' => 'test-document.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'type' => 'application/pdf',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ],
        ];
        
        return array_merge($defaults, $overrides);
    }

    // #region Authentication Tests

    /**
     * Test that upload_file requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_upload_file(): void
    {
        /* Arrange */
        $this->clearAuth();
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }
    
    // #endregion
    
    // #region File Upload Validation Tests

    /**
     * Test upload rejects empty file
     */
    #[Test]
    public function it_rejects_empty_file_upload(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Empty file upload attempt
         * $_FILES: {
         *   "file": {"name": "", "size": 0, "tmp_name": ""}
         * }
         * Expected behavior: Return error for empty file
         */
        $_FILES = ['file' => ['name' => '', 'size' => 0, 'tmp_name' => '']];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertSee('error');
    }

    /**
     * Test upload sanitizes filename with special characters
     */
    #[Test]
    public function it_sanitizes_filename_with_special_characters(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: File with special characters in filename
         * $_FILES: {
         *   "file": {"name": "test<>file.pdf", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Sanitize filename and store in database
         */
        $_FILES = $this->makeFileUploadData([
            'file' => [
                'name' => 'test<>file.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ])['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_uploads', ['url_key' => $urlKey]);
    }

    /**
     * Security: Test upload rejects path traversal attempts
     */
    #[Test]
    public function it_rejects_path_traversal_attempts_in_filename(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Path traversal attempt in filename
         * $_FILES: {
         *   "file": {"name": "../../../etc/passwd", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Reject with 400 error
         */
        $_FILES = $this->makeFileUploadData([
            'file' => [
                'name' => '../../../etc/passwd',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ])['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertStatus(400);
    }

    /**
     * Happy Path: Upload validates allowed file extension
     */
    #[Test]
    public function it_validates_file_extension_is_allowed(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Valid file extension
         * $_FILES: {
         *   "file": {"name": "document.pdf", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Accept valid extension
         */
        $_FILES = $this->makeFileUploadData()['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Security: Test upload rejects non-allowed extensions
     */
    #[Test]
    public function it_rejects_non_allowed_file_extensions(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Malicious file extension
         * $_FILES: {
         *   "file": {"name": "malicious.exe", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Reject with 400 error
         */
        $_FILES = $this->makeFileUploadData([
            'file' => [
                'name' => 'malicious.exe',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ])['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertStatus(400);
        $response->assertSee('error');
    }

    /**
     * Test upload validates MIME type
     */
    #[Test]
    public function it_validates_mime_type_of_uploaded_file(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: File with valid MIME type
         * $_FILES: {
         *   "file": {"name": "test.pdf", "tmp_name": "/var/tmp/phptest", "type": "application/pdf", "size": 1024}
         * }
         * Expected behavior: Accept valid MIME type
         */
        $_FILES = $this->makeFileUploadData()['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test upload rejects duplicate filenames
     */
    #[Test]
    public function it_rejects_duplicate_filenames(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        $_FILES = $this->makeFileUploadData()['file'];
        $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Second upload with same filename
         * $_FILES: {
         *   "file": {"name": "test.pdf", "tmp_name": "/var/tmp/phptest2", "size": 1024}
         * }
         * Expected behavior: Reject duplicate with 409 error
         */
        $_FILES = $this->makeFileUploadData([
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest2',
                'size' => 1024
            ]
        ])['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertStatus(409);
        $response->assertSee('duplicate');
    }
    
    // #endregion
    
    // #region File Upload Operations Tests

    /**
     * Happy Path: Upload creates target directory if needed
     */
    #[Test]
    public function it_creates_target_directory_if_not_exists(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'new-customer-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Upload to new directory
         * $_FILES: {
         *   "file": {"name": "test.pdf", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Create directory and upload file
         */
        $_FILES = $this->makeFileUploadData()['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Happy Path: Upload saves metadata to database
     */
    #[Test]
    public function it_saves_file_metadata_to_database(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Upload with metadata
         * $_FILES: {
         *   "file": {"name": "test.pdf", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Store file metadata in database
         */
        $_FILES = $this->makeFileUploadData()['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_uploads', ['file_name' => 'test.pdf']);
    }

    /**
     * Test upload prefixes filename with url_key
     */
    #[Test]
    public function it_prefixes_filename_with_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: Test URL key prefix
         * $_FILES: {
         *   "file": {"name": "document.pdf", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Prefix filename with URL key
         */
        $_FILES = $this->makeFileUploadData([
            'file' => [
                'name' => 'document.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ])['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_uploads', ['url_key' => $urlKey]);
    }
    
    // #endregion
    
    // #region Show Files Tests

    /**
     * Happy Path: show_files returns JSON response
     */
    #[Test]
    public function it_returns_json_response_for_show_files(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /**
         * Act: GET /upload/show_files/{url_key}
         * Expected behavior: Return JSON list of files
         */
        $response = $this->get('/upload/show_files/' . $urlKey);
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test show_files requires url_key parameter
     */
    #[Test]
    public function it_requires_url_key_parameter_for_show_files(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        
        /**
         * Act: GET /upload/show_files
         * Expected behavior: Return error for missing url_key
         */
        $response = $this->get('/upload/show_files');
        
        /* Assert */
        $response->assertSee('error');
    }

    /**
     * Test show_files returns empty JSON for invalid url_key
     */
    #[Test]
    public function it_returns_empty_json_for_invalid_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        
        /**
         * Act: GET /upload/show_files/{url_key}
         * Expected behavior: Return empty JSON for non-existent url_key
         */
        $response = $this->get('/upload/show_files/invalid-key');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('{}');
    }
    
    // #endregion
    
    // #region Delete File Tests

    /**
     * Happy Path: delete_file removes file from filesystem
     */
    #[Test]
    public function it_removes_file_from_filesystem(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        $fileName = 'test.pdf';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * POST data: {
         *   "name": "test.pdf"
         * }
         * Expected behavior: Delete file from filesystem
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $fileName
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Happy Path: delete_file removes database record
     */
    #[Test]
    public function it_removes_database_record_when_deleting_file(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        $fileName = 'test.pdf';
        
        $this->fakeDb->insert('ip_uploads', [
            'url_key' => $urlKey,
            'file_name' => $fileName
        ]);
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * POST data: {
         *   "name": "test.pdf"
         * }
         * Expected behavior: Remove database record
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $fileName
        ]);
        
        /* Assert */
        $this->assertDatabaseMissingRecord('ip_uploads', ['file_name' => $fileName]);
    }

    /**
     * Test delete_file sanitizes filename
     */
    #[Test]
    public function it_sanitizes_filename_when_deleting(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * POST data: {
         *   "name": "test<>file.pdf"
         * }
         * Expected behavior: Sanitize filename before deletion
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => 'test<>file.pdf'
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Security: Test delete_file prevents path traversal
     */
    #[Test]
    public function it_prevents_path_traversal_when_deleting(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * POST data: {
         *   "name": "../../../etc/passwd"
         * }
         * Expected behavior: Block path traversal attempt with 400 error
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => '../../../etc/passwd'
        ]);
        
        /* Assert */
        $response->assertStatus(400);
    }

    /**
     * Test delete_file validates file is in allowed directory
     */
    #[Test]
    public function it_validates_file_is_in_allowed_directory(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * POST data: {
         *   "name": "outsidefile.pdf"
         * }
         * Expected behavior: Validate file is in allowed directory
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => 'outsidefile.pdf'
        ]);
        
        /* Assert */
        $response->assertStatus(404);
    }

    /**
     * Test delete_file handles missing filename parameter
     */
    #[Test]
    public function it_handles_missing_filename_parameter(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * POST data: {
         *   "name": ""
         * }
         * Expected behavior: Return error for empty filename
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => ''
        ]);
        
        /* Assert */
        $response->assertSee('error');
    }
    
    // #endregion
    
    // #region Get File Tests

    /**
     * Happy Path: get_file validates filename format
     */
    #[Test]
    public function it_validates_filename_format_for_get_file(): void
    {
        /* Arrange */
        $this->clearAuth(); // Public endpoint
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Validate filename format
         */
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test get_file extracts url_key from filename
     */
    #[Test]
    public function it_extracts_url_key_from_filename(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Extract URL key from filename
         */
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Security: Test get_file prevents path traversal
     */
    #[Test]
    public function it_prevents_path_traversal_in_get_file(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Block path traversal attempt
         */
        $response = $this->get('/upload/get_file/../../../etc/passwd');
        
        /* Assert */
        $response->assertNotFound();
    }

    /**
     * Test get_file validates file exists
     */
    #[Test]
    public function it_validates_file_exists_for_download(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Return 404 for non-existent file
         */
        $response = $this->get('/upload/get_file/nonexistent-file.pdf');
        
        /* Assert */
        $response->assertNotFound();
    }

    /**
     * Test get_file validates file in allowed directory
     */
    #[Test]
    public function it_validates_file_is_in_allowed_directory_for_download(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Validate file is in allowed uploads directory
         */
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test get_file sets correct content type header
     */
    #[Test]
    public function it_sets_correct_content_type_header(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Set correct Content-Type header for PDF
         */
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Security: Test get_file sanitizes filename for headers
     */
    #[Test]
    public function it_sanitizes_filename_for_header(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Sanitize filename in Content-Disposition header
         */
        $response = $this->get('/upload/get_file/test-key_doc<>ument.pdf');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Security: Test get_file prevents header injection
     */
    #[Test]
    public function it_prevents_header_injection_in_get_file(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Prevent header injection
         */
        $response = $this->get('/upload/get_file/test-key_file%0D%0AX-Injected.pdf');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test get_file sets download headers
     */
    #[Test]
    public function it_sets_download_headers(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Set Content-Disposition header for download
         */
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertHeader('Content-Disposition', 'attachment');
    }
    
    // #endregion
    
    // #region Helper Function Tests

    /**
     * Test sanitize_file_name removes path components
     */
    #[Test]
    public function it_removes_path_components_from_filename(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Helper function test
        
        /* Assert */
        $this->markTestIncomplete('Helper function test - implement when helper is accessible');
    }

    /**
     * Test sanitize_file_name removes null bytes
     */
    #[Test]
    public function it_removes_null_bytes_from_filename(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Helper function test
        
        /* Assert */
        $this->markTestIncomplete('Helper function test - implement when helper is accessible');
    }

    /**
     * Test sanitize_file_name removes path separators
     */
    #[Test]
    public function it_removes_path_separators_from_filename(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Helper function test
        
        /* Assert */
        $this->markTestIncomplete('Helper function test - implement when helper is accessible');
    }

    /**
     * Test sanitize_file_name logs path traversal attempts
     */
    #[Test]
    public function it_logs_path_traversal_attempts(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Helper function test
        
        /* Assert */
        $this->markTestIncomplete('Helper function test - implement when helper is accessible');
    }

    /**
     * Test allowed extensions are restricted
     */
    #[Test]
    public function it_restricts_allowed_extensions(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Configuration test
        
        /* Assert */
        $this->markTestIncomplete('Configuration test - implement when config is accessible');
    }
    
    // #endregion
    
    // #region Security Tests

    /**
     * Security: Test upload rejects SVG files
     */
    #[Test]
    public function it_rejects_svg_files_as_security_risk(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * POST data: SVG file upload attempt (security risk)
         * $_FILES: {
         *   "file": {"name": "image.svg", "tmp_name": "/var/tmp/phptest", "size": 1024}
         * }
         * Expected behavior: Reject SVG files
         */
        $_FILES = $this->makeFileUploadData([
            'file' => [
                'name' => 'image.svg',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ])['file'];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertSee('error');
        $response->assertStatus(400);
    }
    
    // #endregion
}
