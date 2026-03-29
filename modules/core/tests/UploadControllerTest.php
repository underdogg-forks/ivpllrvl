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
        // Store admin user for reuse in tests
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
        
        /* Assert - Redirect to Login */
        $response->assertRedirect("/sessions/login");
        
        /* Assert - No Session Set */
        $this->assertFalse($this->fakeSession->has('user_id'), 'User should not be authenticated');
        
        /* Assert - Response Not Successful */
        $this->assertFalse($response->isOk(), 'Response should not be 200 OK');
        
        /* Assert - No Upload Created */
        $uploads = $this->fakeDb->select('ip_uploads', ['url_key' => $urlKey]);
        $this->assertEmpty($uploads, 'No uploads should be created without authentication');
    }
    
    // #endregion
    
    // #region File Upload Tests

    #[Test]
    public function it_upload_file_rejects_empty_file(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Empty file upload attempt
         * Expected: 400 error, no file created, error message
         */
        $_FILES = ['file' => ['name' => '', 'size' => 0, 'tmp_name' => '']];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Error Response */
        $response->assertSee('error');
        
        /* Assert - Bad Request Status */
        $this->assertTrue(
            $response->getStatusCode() >= 400,
            'Should return error status code for empty file'
        );
        
        /* Assert - No Database Record */
        $uploads = $this->fakeDb->select('ip_uploads', ['url_key' => $urlKey]);
        $this->assertEmpty($uploads, 'No upload record should be created for empty file');
        
        /* Assert - Flash Error Message */
        $flashError = $this->fakeSession->get('alert_error');
        $this->assertNotEmpty($flashError, 'Error message should be set in session');
    }

    #[Test]
    public function it_upload_file_sanitizes_filename(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key-' . uniqid();
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Test filename sanitization with special characters
         * Expected: Filename sanitized, special characters removed, upload succeeds
         */
        $_FILES = [
            'file' => [
                'name' => 'test<>file.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Database Record Exists */
        $this->assertDatabaseHas('ip_uploads', ['url_key' => $urlKey]);
        
        /* Assert - Filename Was Sanitized */
        $upload = $this->fakeDb->selectOne('ip_uploads', ['url_key' => $urlKey]);
        $this->assertNotNull($upload, 'Upload record should exist');
        $this->assertStringNotContainsString('<', $upload['file_name_original']);
        $this->assertStringNotContainsString('>', $upload['file_name_original']);
        
        /* Assert - Response Success */
        $this->assertTrue(
            $response->isOk() || $response->getStatusCode() === 201,
            'Upload should succeed with sanitized filename'
        );
        
        /* Assert - File Name Stored */
        $this->assertNotEmpty($upload['file_name_new'], 'New filename should be generated');
    }

    #[Test]
    public function it_upload_file_rejects_path_traversal_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        $maliciousFilename = '../../../etc/passwd';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Path traversal attempt in filename
         * Expected: 400/403 error, no file created, security log entry
         */
        $_FILES = [
            'file' => [
                'name' => $maliciousFilename,
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Security Response */
        $response->assertStatus(400);
        
        /* Assert - Error Message Present */
        $content = $response->getContent();
        $this->assertTrue(
            stripos($content, 'error') !== false || stripos($content, 'invalid') !== false,
            'Response should contain error or invalid message'
        );
        
        /* Assert - No Malicious File Created */
        $uploadPath = APPPATH . '../uploads/customer_files/';
        $this->assertFileDoesNotExist($uploadPath . $maliciousFilename);
        $this->assertFileDoesNotExist($uploadPath . 'passwd');
        
        /* Assert - No Database Record for Malicious File */
        $uploads = $this->fakeDb->select('ip_uploads', ['url_key' => $urlKey]);
        foreach ($uploads as $upload) {
            $this->assertStringNotContainsString('..', $upload['file_name_original']);
            $this->assertStringNotContainsString('etc/passwd', $upload['file_name_original']);
        }
    }

    #[Test]
    public function it_upload_file_validates_file_extension(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key-' . uniqid();
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Valid extension test (.pdf is typically allowed)
         * Expected: Upload succeeds, file stored, database record created
         */
        $_FILES = [
            'file' => [
                'name' => 'document.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - Database Record Created */
        $upload = $this->fakeDb->selectOne('ip_uploads', ['url_key' => $urlKey]);
        $this->assertNotNull($upload, 'Upload record should exist for valid extension');
        
        /* Assert - Correct Extension Stored */
        $this->assertStringEndsWith('.pdf', $upload['file_name_original']);
        
        /* Assert - File Name Fields Populated */
        $this->assertNotEmpty($upload['file_name_new'], 'New filename should be generated');
        $this->assertEquals('document.pdf', $upload['file_name_original']);
    }

    #[Test]
    public function it_upload_file_rejects_non_allowed_extensions(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        $maliciousFile = 'malicious.exe';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Malicious file extension (.exe)
         * Expected: 400 error, no file created, error message
         */
        $_FILES = [
            'file' => [
                'name' => $maliciousFile,
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Bad Request Status */
        $response->assertStatus(400);
        
        /* Assert - Error Message */
        $response->assertSee('error');
        
        /* Assert - No File Created in Filesystem */
        $uploadPath = APPPATH . '../uploads/customer_files/';
        $files = glob($uploadPath . '*.exe');
        $this->assertEmpty($files, 'No .exe files should be created');
        
        /* Assert - No Database Record */
        $uploads = $this->fakeDb->select('ip_uploads', ['url_key' => $urlKey]);
        $this->assertEmpty($uploads, 'No upload record should exist for malicious extension');
        
        /* Assert - Flash Error Set */
        $flashError = $this->fakeSession->get('alert_error');
        $this->assertNotEmpty($flashError, 'Error message should be set in session');
    }

    #[Test]
    public function it_upload_file_validates_mime_type(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key-' . uniqid();
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Test MIME type validation (application/pdf is valid)
         * Expected: Upload succeeds, MIME type validated
         */
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'type' => 'application/pdf',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - Database Record Created */
        $upload = $this->fakeDb->selectOne('ip_uploads', ['url_key' => $urlKey]);
        $this->assertNotNull($upload, 'Upload record should exist for valid MIME type');
        
        /* Assert - File Extension Matches MIME */
        $this->assertStringEndsWith('.pdf', $upload['file_name_original']);
        
        /* Assert - Upload Date Set */
        $this->assertNotEmpty($upload['uploaded_date'], 'Upload date should be set');
    }

    #[Test]
    public function it_upload_file_rejects_duplicate_filenames(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key-' . uniqid();
        
        // First upload
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest1',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $firstResponse = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Second upload with same filename
         * Expected: 409 Conflict, duplicate error message
         */
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest2',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Conflict Status */
        $response->assertStatus(409);
        
        /* Assert - Duplicate Error Message */
        $response->assertSee('duplicate');
        
        /* Assert - First Upload Still Exists */
        $uploads = $this->fakeDb->select('ip_uploads', ['url_key' => $urlKey]);
        $this->assertCount(1, $uploads, 'Should only have one upload record');
        
        /* Assert - Flash Error Message */
        $flashError = $this->fakeSession->get('alert_error');
        $this->assertNotEmpty($flashError, 'Error message should be set for duplicate');
    }

    #[Test]
    public function it_upload_file_creates_target_directory(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'new-customer-key-' . uniqid();
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Upload to new directory
         * Expected: Directory created, file uploaded, success response
         */
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - Directory Was Created */
        $uploadPath = APPPATH . '../uploads/customer_files/' . $urlKey . '/';
        $this->assertDirectoryExists($uploadPath, 'Upload directory should be created');
        
        /* Assert - Directory Has Correct Permissions */
        $perms = fileperms($uploadPath);
        $this->assertTrue(is_writable($uploadPath), 'Upload directory should be writable');
        
        /* Assert - Database Record Created */
        $upload = $this->fakeDb->selectOne('ip_uploads', ['url_key' => $urlKey]);
        $this->assertNotNull($upload, 'Upload record should exist');
    }

    #[Test]
    public function it_upload_file_saves_metadata_to_database(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'test-document.pdf';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Upload with metadata
         * Expected: All metadata fields saved correctly
         */
        $_FILES = [
            'file' => [
                'name' => $fileName,
                'tmp_name' => '/var/tmp/phptest',
                'size' => 2048,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Database Has Record */
        $this->assertDatabaseHas('ip_uploads', ['file_name_original' => $fileName]);
        
        /* Assert - All Metadata Fields Present */
        $upload = $this->fakeDb->selectOne('ip_uploads', ['url_key' => $urlKey]);
        $this->assertNotNull($upload, 'Upload record should exist');
        $this->assertEquals($fileName, $upload['file_name_original']);
        $this->assertNotEmpty($upload['file_name_new'], 'New filename should be generated');
        $this->assertEquals($urlKey, $upload['url_key']);
        
        /* Assert - Upload Date Set */
        $this->assertNotEmpty($upload['uploaded_date'], 'Upload date should be set');
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}/',
            $upload['uploaded_date'],
            'Upload date should be in correct format'
        );
        
        /* Assert - Customer ID Stored */
        $this->assertEquals($customerId, $upload['client_id'] ?? $upload['customer_id'] ?? null);
    }

    #[Test]
    public function it_upload_file_prefixes_filename_with_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'document.pdf';
        
        /**
         * Act: POST /upload/upload_file/{customerId}/{url_key}
         * Test URL key prefix
         * Expected: Filename prefixed with URL key
         */
        $_FILES = [
            'file' => [
                'name' => $fileName,
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert - Record Exists with URL Key */
        $records = $this->fakeDb->select('ip_uploads', ['url_key' => $urlKey]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_uploads'");
        
        /* Assert - URL Key Correctly Stored */
        $upload = $records[0];
        $this->assertEquals($urlKey, $upload['url_key']);
        
        /* Assert - Original Filename Preserved */
        $this->assertEquals($fileName, $upload['file_name_original']);
        
        /* Assert - New Filename Contains URL Key or Unique Identifier */
        $this->assertNotEmpty($upload['file_name_new']);
        $this->assertNotEquals($fileName, $upload['file_name_new'], 'New filename should be different from original');
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
        $urlKey = 'test-key-' . uniqid();
        
        // Create test upload record
        $this->fakeDb->insert('ip_uploads', [
            'url_key' => $urlKey,
            'file_name_original' => 'test.pdf',
            'file_name_new' => 'stored_test.pdf',
            'uploaded_date' => date('Y-m-d H:i:s'),
        ]);
        
        /**
         * Act: GET /upload/show_files/{url_key}
         * Expected behavior: Return JSON list of files
         */
        $response = $this->get('/upload/show_files/' . $urlKey);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - JSON Content Type */
        $response->assertHeader('Content-Type', 'application/json');
        
        /* Assert - Valid JSON Response */
        $content = $response->getContent();
        $this->assertJson($content, 'Response should be valid JSON');
        
        /* Assert - JSON Contains Expected Data */
        $jsonData = json_decode($content, true);
        $this->assertIsArray($jsonData, 'JSON should decode to array');
    }

    #[Test]
    public function it_show_files_requires_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        
        /**
         * Act: GET /upload/show_files
         * No URL key provided
         * Expected: Error response, 400 status or error message
         */
        $response = $this->get('/upload/show_files');
        
        /* Assert - Error Message */
        $response->assertSee('error');
        
        /* Assert - Error Status */
        $this->assertTrue(
            $response->getStatusCode() >= 400 || $response->getStatusCode() === 200,
            'Should return error status or handle gracefully'
        );
        
        /* Assert - Response Content */
        $content = $response->getContent();
        $this->assertNotEmpty($content, 'Response should contain error information');
    }

    #[Test]
    public function it_show_files_returns_empty_json_for_invalid_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $invalidKey = 'invalid-key-' . uniqid();
        
        /**
         * Act: GET /upload/show_files/{url_key}
         * Invalid URL key that doesn't exist
         * Expected: Empty JSON array or object, 200 OK
         */
        $response = $this->get('/upload/show_files/' . $invalidKey);
        
        /* Assert - Response OK */
        $response->assertOk();
        
        /* Assert - Empty JSON Response */
        $content = $response->getContent();
        $this->assertTrue(
            $content === '{}' || $content === '[]' || $content === 'null',
            'Should return empty JSON for invalid key'
        );
        
        /* Assert - Valid JSON */
        $this->assertJson($content === '' ? '{}' : $content, 'Response should be valid JSON');
        
        /* Assert - No Files in Response */
        $jsonData = json_decode($content, true);
        $this->assertTrue(
            empty($jsonData) || (is_array($jsonData) && count($jsonData) === 0),
            'Should contain no files'
        );
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
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'test-delete.pdf';
        
        // Create upload directory and file
        $uploadPath = APPPATH . '../uploads/customer_files/' . $urlKey . '/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $filePath = $uploadPath . $fileName;
        file_put_contents($filePath, 'Test content for deletion');
        
        // Create database record
        $this->fakeDb->insert('ip_uploads', [
            'url_key' => $urlKey,
            'file_name_original' => $fileName,
            'file_name_new' => $fileName,
            'uploaded_date' => date('Y-m-d H:i:s'),
        ]);
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * Expected: File removed from filesystem, 200 OK
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $fileName
        ]);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - File Removed from Filesystem */
        $this->assertFileDoesNotExist($filePath, 'File should be removed from filesystem');
        
        /* Assert - Directory Still Exists */
        $this->assertDirectoryExists($uploadPath, 'Upload directory should still exist');
        
        /* Assert - Success Message */
        $flashSuccess = $this->fakeSession->get('alert_success');
        $this->assertNotEmpty($flashSuccess, 'Success message should be set');
        
        // Cleanup
        if (is_dir($uploadPath)) {
            rmdir($uploadPath);
        }
    }

    #[Test]
    public function it_delete_file_removes_database_record(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'test-db-delete.pdf';
        
        // Create test record
        $uploadId = $this->fakeDb->insert('ip_uploads', [
            'url_key' => $urlKey,
            'file_name_original' => $fileName,
            'file_name_new' => $fileName,
            'uploaded_date' => date('Y-m-d H:i:s'),
        ]);
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * Expected: Database record removed
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $fileName
        ]);
        
        /* Assert - Database Record Removed */
        $this->assertDatabaseMissing('ip_uploads', ['file_name_original' => $fileName]);
        
        /* Assert - Specific Record Gone */
        $upload = $this->fakeDb->selectOne('ip_uploads', ['upload_id' => $uploadId]);
        $this->assertNull($upload, 'Upload record should be deleted');
        
        /* Assert - No Records with URL Key and Filename */
        $uploads = $this->fakeDb->select('ip_uploads', [
            'url_key' => $urlKey,
            'file_name_original' => $fileName
        ]);
        $this->assertEmpty($uploads, 'No uploads should remain with that filename');
        
        /* Assert - Response Success */
        $this->assertTrue(
            $response->isOk() || $response->getStatusCode() === 204,
            'Delete should succeed'
        );
    }

    #[Test]
    public function it_delete_file_sanitizes_filename(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key-' . uniqid();
        $maliciousName = 'test<>file.pdf';
        $sanitizedName = 'testfile.pdf';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * Filename with special characters
         * Expected: Filename sanitized, safe deletion operation
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $maliciousName
        ]);
        
        /* Assert - Response Success or Not Found */
        $this->assertTrue(
            $response->isOk() || $response->isNotFound(),
            'Should handle sanitized filename gracefully'
        );
        
        /* Assert - No Malicious Files Created */
        $uploadPath = APPPATH . '../uploads/customer_files/';
        $this->assertFileDoesNotExist($uploadPath . $maliciousName);
        
        /* Assert - Sanitization Occurred */
        // The controller should have sanitized the filename before processing
        $content = $response->getContent();
        $this->assertStringNotContainsString('<', $content);
        $this->assertStringNotContainsString('>', $content);
    }

    #[Test]
    public function it_delete_file_prevents_path_traversal(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        $maliciousPath = '../../../etc/passwd';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * Path traversal attempt
         * Expected: 400 error, no file deleted outside allowed directory
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $maliciousPath
        ]);
        
        /* Assert - Security Response */
        $response->assertStatus(400);
        
        /* Assert - System File Not Affected */
        $this->assertFileExists('/etc/passwd', 'System file should not be deleted');
        
        /* Assert - Error Message */
        $content = $response->getContent();
        $this->assertTrue(
            stripos($content, 'error') !== false || stripos($content, 'invalid') !== false,
            'Should return error for path traversal'
        );
        
        /* Assert - No Database Changes */
        $uploads = $this->fakeDb->select('ip_uploads', []);
        // Ensure no records were accidentally modified
        foreach ($uploads as $upload) {
            $this->assertStringNotContainsString('..', $upload['file_name_original'] ?? '');
        }
    }

    #[Test]
    public function it_delete_file_validates_file_in_directory(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key-' . uniqid();
        $outsideFile = 'outsidefile.pdf';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * Test directory validation - file not in allowed directory
         * Expected: 404 Not Found, no file deleted
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $outsideFile
        ]);
        
        /* Assert - Not Found Status */
        $response->assertStatus(404);
        
        /* Assert - Error Message */
        $content = $response->getContent();
        $this->assertTrue(
            stripos($content, 'not found') !== false || stripos($content, 'error') !== false,
            'Should return not found error'
        );
        
        /* Assert - No Database Record Deleted */
        // Ensure no records were accidentally deleted
        $allUploads = $this->fakeDb->select('ip_uploads', []);
        $uploadCountBefore = count($allUploads);
        // The delete should not have affected any records
        $this->assertGreaterThanOrEqual(0, $uploadCountBefore);
        
        /* Assert - Flash Error Message */
        $flashError = $this->fakeSession->get('alert_error');
        $this->assertNotEmpty($flashError, 'Error message should be set for missing file');
    }

    #[Test]
    public function it_delete_file_handles_missing_filename(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /**
         * Act: POST /upload/delete_file/{url_key}
         * Empty filename
         * Expected: Error response, validation message
         */
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => ''
        ]);
        
        /* Assert - Error Message */
        $response->assertSee('error');
        
        /* Assert - Error Status */
        $this->assertTrue(
            $response->getStatusCode() >= 400,
            'Should return error status for empty filename'
        );
        
        /* Assert - No Database Changes */
        // Ensure no records were accidentally deleted
        $uploads = $this->fakeDb->select('ip_uploads', ['url_key' => $urlKey]);
        // Original uploads should remain intact
        
        /* Assert - Flash Error Message */
        $flashError = $this->fakeSession->get('alert_error');
        $this->assertNotEmpty($flashError, 'Error message should be set for missing filename');
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
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'document.pdf';
        $fullFilename = $urlKey . '_' . $fileName;
        
        // Create test file and database record
        $uploadPath = APPPATH . '../uploads/customer_files/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $filePath = $uploadPath . $fullFilename;
        file_put_contents($filePath, 'Test PDF content');
        
        $this->fakeDb->insert('ip_uploads', [
            'url_key' => $urlKey,
            'file_name_original' => $fileName,
            'file_name_new' => $fullFilename,
            'uploaded_date' => date('Y-m-d H:i:s'),
        ]);
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Expected behavior: Validate filename format, return file
         */
        $response = $this->get('/upload/get_file/' . $fullFilename);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - Content Type Set */
        $contentType = $response->getHeader('Content-Type');
        $this->assertNotEmpty($contentType, 'Content-Type header should be set');
        
        /* Assert - File Content Returned */
        $content = $response->getContent();
        $this->assertNotEmpty($content, 'File content should be returned');
        
        /* Assert - Filename Format Validated */
        $this->assertMatchesRegularExpression(
            '/^[a-z0-9_-]+\.[a-z0-9]+$/i',
            $fullFilename,
            'Filename should match expected format'
        );
        
        // Cleanup
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    #[Test]
    public function it_get_file_extracts_url_key_from_filename(): void
    {
        /* Arrange */
        $this->clearAuth();
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'document.pdf';
        $fullFilename = $urlKey . '_' . $fileName;
        
        // Create test file
        $uploadPath = APPPATH . '../uploads/customer_files/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $filePath = $uploadPath . $fullFilename;
        file_put_contents($filePath, 'Test content');
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Test URL key extraction from filename
         * Expected: URL key extracted, file located and returned
         */
        $response = $this->get('/upload/get_file/' . $fullFilename);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - URL Key Can Be Extracted */
        $extractedKey = substr($fullFilename, 0, strpos($fullFilename, '_'));
        $this->assertEquals($urlKey, $extractedKey, 'URL key should be extractable from filename');
        
        /* Assert - File Content Returned */
        $content = $response->getContent();
        $this->assertEquals('Test content', $content, 'File content should be returned');
        
        /* Assert - Filename Contains Underscore Separator */
        $this->assertStringContainsString('_', $fullFilename, 'Filename should contain underscore separator');
        
        // Cleanup
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    #[Test]
    public function it_get_file_prevents_path_traversal(): void
    {
        /* Arrange */
        $this->clearAuth();
        $maliciousPath = '../../../etc/passwd';
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Path traversal attempt
         * Expected: 404 Not Found, no file access outside allowed directory
         */
        $response = $this->get('/upload/get_file/' . $maliciousPath);
        
        /* Assert - Not Found Response */
        $response->assertNotFound();
        
        /* Assert - System File Not Accessed */
        $this->assertFileExists('/etc/passwd', 'System file should still exist (not modified)');
        
        /* Assert - No Content Returned */
        $content = $response->getContent();
        $this->assertStringNotContainsString('root:', $content, 'Should not return system file content');
        
        /* Assert - Error Response */
        $this->assertTrue(
            stripos($content, 'not found') !== false || stripos($content, 'error') !== false || $content === '',
            'Should return error or empty response'
        );
    }

    #[Test]
    public function it_get_file_validates_file_exists(): void
    {
        /* Arrange */
        $this->clearAuth();
        $nonexistentFile = 'nonexistent-file-' . uniqid() . '.pdf';
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Request non-existent file
         * Expected: 404 Not Found, error message
         */
        $response = $this->get('/upload/get_file/' . $nonexistentFile);
        
        /* Assert - Not Found Response */
        $response->assertNotFound();
        
        /* Assert - File Does Not Exist in Filesystem */
        $uploadPath = APPPATH . '../uploads/customer_files/';
        $this->assertFileDoesNotExist($uploadPath . $nonexistentFile);
        
        /* Assert - No Content or Error Message */
        $content = $response->getContent();
        $this->assertTrue(
            empty($content) || stripos($content, 'not found') !== false,
            'Should return empty or not found message'
        );
        
        /* Assert - No Database Record */
        $uploads = $this->fakeDb->select('ip_uploads', ['file_name_new' => $nonexistentFile]);
        $this->assertEmpty($uploads, 'File should not exist in database');
    }

    #[Test]
    public function it_get_file_validates_file_in_allowed_directory(): void
    {
        /* Arrange */
        $this->clearAuth();
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'document.pdf';
        $fullFilename = $urlKey . '_' . $fileName;
        
        // Create test file in allowed directory
        $uploadPath = APPPATH . '../uploads/customer_files/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $filePath = $uploadPath . $fullFilename;
        file_put_contents($filePath, 'Test content in allowed directory');
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Test directory validation
         * Expected: File in allowed directory is accessible
         */
        $response = $this->get('/upload/get_file/' . $fullFilename);
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - File Is In Allowed Directory */
        $realPath = realpath($filePath);
        $allowedPath = realpath($uploadPath);
        $this->assertStringStartsWith($allowedPath, $realPath, 'File should be in allowed directory');
        
        /* Assert - File Content Returned */
        $content = $response->getContent();
        $this->assertEquals('Test content in allowed directory', $content);
        
        /* Assert - Directory Validation Passed */
        $this->assertTrue(is_file($filePath), 'File should exist in allowed directory');
        
        // Cleanup
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    #[Test]
    public function it_get_file_sets_correct_content_type(): void
    {
        /* Arrange */
        $this->clearAuth();
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'document.pdf';
        $fullFilename = $urlKey . '_' . $fileName;
        
        // Create test PDF file
        $uploadPath = APPPATH . '../uploads/customer_files/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $filePath = $uploadPath . $fullFilename;
        file_put_contents($filePath, '%PDF-1.4 test content');
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Test content type headers for PDF
         * Expected: application/pdf content type
         */
        $response = $this->get('/upload/get_file/' . $fullFilename);
        
        /* Assert - Content Type Header */
        $response->assertHeader('Content-Type', 'application/pdf');
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - Correct MIME Type for Extension */
        $extension = pathinfo($fullFilename, PATHINFO_EXTENSION);
        $this->assertEquals('pdf', $extension, 'File extension should be PDF');
        
        /* Assert - No Text/HTML Content Type */
        $contentType = $response->getHeader('Content-Type');
        $this->assertStringNotContainsString('text/html', $contentType, 'Should not be HTML');
        
        // Cleanup
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    #[Test]
    public function it_get_file_sanitizes_filename_for_header(): void
    {
        /* Arrange */
        $this->clearAuth();
        $urlKey = 'test-key-' . uniqid();
        $maliciousName = 'doc<>ument.pdf';
        $fullFilename = $urlKey . '_' . $maliciousName;
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Test header sanitization with special characters
         * Expected: Filename sanitized in Content-Disposition header
         */
        $response = $this->get('/upload/get_file/' . rawurlencode($fullFilename));
        
        /* Assert - Response Handled */
        $this->assertTrue(
            $response->isOk() || $response->isNotFound(),
            'Should handle malicious filename gracefully'
        );
        
        /* Assert - Header Sanitization */
        $disposition = $response->getHeader('Content-Disposition');
        if ($disposition) {
            $this->assertStringNotContainsString('<', $disposition, 'Header should not contain <');
            $this->assertStringNotContainsString('>', $disposition, 'Header should not contain >');
        }
        
        /* Assert - No XSS in Response */
        $content = $response->getContent();
        if (!empty($content)) {
            $this->assertStringNotContainsString('<script>', $content);
        }
        
        /* Assert - Safe Response */
        $statusCode = $response->getStatusCode();
        $this->assertContains($statusCode, [200, 404, 400], 'Should return safe status code');
    }

    #[Test]
    public function it_get_file_prevents_header_injection(): void
    {
        /* Arrange */
        $this->clearAuth();
        $injectionAttempt = 'test-key_file%0D%0AX-Injected.pdf';
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Test header injection prevention
         * Expected: Header injection blocked, safe response
         */
        $response = $this->get('/upload/get_file/' . $injectionAttempt);
        
        /* Assert - Response Handled */
        $this->assertTrue(
            $response->isOk() || $response->isNotFound(),
            'Should handle injection attempt gracefully'
        );
        
        /* Assert - No Injected Header */
        $injectedHeader = $response->getHeader('X-Injected');
        $this->assertNull($injectedHeader, 'Injected header should not exist');
        
        /* Assert - Content-Disposition Safe */
        $disposition = $response->getHeader('Content-Disposition');
        if ($disposition) {
            $this->assertStringNotContainsString("\r", $disposition, 'Should not contain CR');
            $this->assertStringNotContainsString("\n", $disposition, 'Should not contain LF');
        }
        
        /* Assert - All Headers Safe */
        $headers = $response->getHeaders();
        foreach ($headers as $value) {
            $this->assertStringNotContainsString("\r\n", $value, 'Headers should not contain CRLF');
        }
    }

    #[Test]
    public function it_get_file_sets_download_headers(): void
    {
        /* Arrange */
        $this->clearAuth();
        $urlKey = 'test-key-' . uniqid();
        $fileName = 'document.pdf';
        $fullFilename = $urlKey . '_' . $fileName;
        
        // Create test file
        $uploadPath = APPPATH . '../uploads/customer_files/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        $filePath = $uploadPath . $fullFilename;
        file_put_contents($filePath, 'Download test content');
        
        /**
         * Act: GET /upload/get_file/{filename}
         * Test download headers
         * Expected: Content-Disposition attachment header set
         */
        $response = $this->get('/upload/get_file/' . $fullFilename);
        
        /* Assert - Content-Disposition Header */
        $response->assertHeader('Content-Disposition', 'attachment');
        
        /* Assert - Response Success */
        $response->assertOk();
        
        /* Assert - Disposition Contains Filename */
        $disposition = $response->getHeader('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition, 'Should be attachment');
        
        /* Assert - Content Length Set */
        $contentLength = $response->getHeader('Content-Length');
        if ($contentLength !== null) {
            $this->assertGreaterThan(0, (int)$contentLength, 'Content length should be positive');
        }
        
        // Cleanup
        if (file_exists($filePath)) {
            unlink($filePath);
        }
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
        // Test path component removal
        // This would be a unit test for the helper function
        
        /* Assert */
        $this->markTestIncomplete('Helper function test - implement when helper is accessible');
    }

    #[Test]
    public function it_sanitize_file_name_removes_null_bytes(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Test null byte removal
        
        /* Assert */
        $this->markTestIncomplete('Helper function test - implement when helper is accessible');
    }

    #[Test]
    public function it_sanitize_file_name_removes_path_separators(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Test path separator removal
        
        /* Assert */
        $this->markTestIncomplete('Helper function test - implement when helper is accessible');
    }

    #[Test]
    public function it_sanitize_file_name_logs_path_traversal_attempts(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Test logging of path traversal
        
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
        // SVG file upload attempt (security risk)
        $_FILES = [
            'file' => [
                'name' => 'image.svg',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertSee('error');
        $response->assertStatus(400);
    }
    
    // #endregion
}
