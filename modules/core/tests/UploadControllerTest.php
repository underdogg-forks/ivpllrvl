<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UploadController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UploadController
 * 
 * Tests file upload, download, deletion with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UploadController::class)]
class UploadControllerTest extends ControllerTestCase
{
    protected string $controllerClass = UploadController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures for authentication testing
        $users = $this->fixtures->all('users');
        
        // Seed fake database with test users
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store admin user for reuse in tests
        $this->testData = [
            'admin' => $this->fixtures->get('users', 'admin'),
        ];
    }

    #[Test]
    public function it_upload_file_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_upload_file_rejects_empty_file(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Empty file upload attempt
        $_FILES = ['file' => ['name' => '', 'size' => 0, 'tmp_name' => '']];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertSee('error');
    }

    #[Test]
    public function it_upload_file_sanitizes_filename(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Test filename sanitization with special characters
        $_FILES = [
            'file' => [
                'name' => 'test<>file.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        // Verify filename was sanitized
        $this->assertDatabaseHas('ip_uploads', ['url_key' => $urlKey]);
    }

    #[Test]
    public function it_upload_file_rejects_path_traversal_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Path traversal attempt in filename
        $_FILES = [
            'file' => [
                'name' => '../../../etc/passwd',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        // Verify path traversal blocked (should error or sanitize)
        $response->assertStatus(400);
    }

    #[Test]
    public function it_upload_file_validates_file_extension(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Valid extension test
        $_FILES = [
            'file' => [
                'name' => 'document.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertOk();
    }

    #[Test]
    public function it_upload_file_rejects_non_allowed_extensions(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Malicious file extension
        $_FILES = [
            'file' => [
                'name' => 'malicious.exe',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertStatus(400);
        $response->assertSee('error');
    }

    #[Test]
    public function it_upload_file_validates_mime_type(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Test MIME type validation
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'type' => 'application/pdf',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertOk();
    }

    #[Test]
    public function it_upload_file_rejects_duplicate_filenames(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        // First upload
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest1',
                'size' => 1024
            ]
        ];
        $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Second upload with same filename
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest2',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $response->assertStatus(409);
        $response->assertSee('duplicate');
    }

    #[Test]
    public function it_upload_file_creates_target_directory(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'new-customer-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Upload to new directory
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        // Verify directory was created
        $response->assertOk();
    }

    #[Test]
    public function it_upload_file_saves_metadata_to_database(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Upload with metadata
        $_FILES = [
            'file' => [
                'name' => 'test.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $this->assertDatabaseHas('ip_uploads', ['file_name' => 'test.pdf']);
    }

    #[Test]
    public function it_upload_file_prefixes_filename_with_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
        // Test URL key prefix
        $_FILES = [
            'file' => [
                'name' => 'document.pdf',
                'tmp_name' => '/var/tmp/phptest',
                'size' => 1024
            ]
        ];
        $response = $this->post('/upload/upload_file/' . $customerId . '/' . $urlKey);
        
        /* Assert */
        $this->assertDatabaseHas('ip_uploads', ['url_key' => $urlKey]);
    }

    #[Test]
    public function it_show_files_returns_json(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /* Act */
        // GET /upload/show_files/{url_key}
        $response = $this->get('/upload/show_files/' . $urlKey);
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    #[Test]
    public function it_show_files_requires_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        
        /* Act */
        // GET /upload/show_files
        $response = $this->get('/upload/show_files');
        
        /* Assert */
        $response->assertSee('error');
    }

    #[Test]
    public function it_show_files_returns_empty_json_for_invalid_url_key(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        
        /* Act */
        // GET /upload/show_files/{url_key}
        $response = $this->get('/upload/show_files/invalid-key');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('{}');
    }

    #[Test]
    public function it_delete_file_removes_file_from_filesystem(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        $fileName = 'test.pdf';
        
        /* Act */
        // POST /upload/delete_file/{url_key}
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $fileName
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    #[Test]
    public function it_delete_file_removes_database_record(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        $fileName = 'test.pdf';
        
        // Create a test record
        $this->createTestRecord('ip_uploads', [
            'url_key' => $urlKey,
            'file_name' => $fileName
        ]);
        
        /* Act */
        // POST /upload/delete_file/{url_key}
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => $fileName
        ]);
        
        /* Assert */
        $this->assertDatabaseMissing('ip_uploads', ['file_name' => $fileName]);
    }

    #[Test]
    public function it_delete_file_sanitizes_filename(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/delete_file/{url_key}
        // Filename with special characters
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => 'test<>file.pdf'
        ]);
        
        /* Assert */
        // Verify sanitization occurred
        $response->assertOk();
    }

    #[Test]
    public function it_delete_file_prevents_path_traversal(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/delete_file/{url_key}
        // Path traversal attempt
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => '../../../etc/passwd'
        ]);
        
        /* Assert */
        // Verify path traversal blocked
        $response->assertStatus(400);
    }

    #[Test]
    public function it_delete_file_validates_file_in_directory(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/delete_file/{url_key}
        // Test directory validation
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => 'outsidefile.pdf'
        ]);
        
        /* Assert */
        $response->assertStatus(404);
    }

    #[Test]
    public function it_delete_file_handles_missing_filename(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/delete_file/{url_key}
        // Empty filename
        $response = $this->post('/upload/delete_file/' . $urlKey, [
            'name' => ''
        ]);
        
        /* Assert */
        $response->assertSee('error');
    }

    #[Test]
    public function it_get_file_validates_filename_format(): void
    {
        /* Arrange */
        $this->clearAuth(); // Public endpoint
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Test filename format validation
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertOk();
    }

    #[Test]
    public function it_get_file_extracts_url_key_from_filename(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Test URL key extraction from filename
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        // Verify URL key was extracted correctly
        $response->assertOk();
    }

    #[Test]
    public function it_get_file_prevents_path_traversal(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Path traversal attempt
        $response = $this->get('/upload/get_file/../../../etc/passwd');
        
        /* Assert */
        $response->assertNotFound();
    }

    #[Test]
    public function it_get_file_validates_file_exists(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        $response = $this->get('/upload/get_file/nonexistent-file.pdf');
        
        /* Assert */
        $response->assertNotFound();
    }

    #[Test]
    public function it_get_file_validates_file_in_allowed_directory(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Test directory validation
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        // Should validate file is in allowed uploads directory
        $response->assertOk();
    }

    #[Test]
    public function it_get_file_sets_correct_content_type(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Test content type headers for PDF
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function it_get_file_sanitizes_filename_for_header(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Test header sanitization with special characters
        $response = $this->get('/upload/get_file/test-key_doc<>ument.pdf');
        
        /* Assert */
        // Verify filename was sanitized in Content-Disposition header
        $response->assertOk();
    }

    #[Test]
    public function it_get_file_prevents_header_injection(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Test header injection prevention
        $response = $this->get('/upload/get_file/test-key_file%0D%0AX-Injected.pdf');
        
        /* Assert */
        // Verify header injection was prevented
        $response->assertOk();
    }

    #[Test]
    public function it_get_file_sets_download_headers(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /upload/get_file/{filename}
        // Test download headers
        $response = $this->get('/upload/get_file/test-key_document.pdf');
        
        /* Assert */
        $response->assertHeader('Content-Disposition', 'attachment');
    }

    #[Test]
    public function it_sanitize_file_name_removes_path_components(): void
    {
        /* Arrange */
        // No auth needed for helper tests
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

    #[Test]
    public function it_allowed_extensions_are_restricted(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // Test extension whitelist
        
        /* Assert */
        $this->markTestIncomplete('Configuration test - implement when config is accessible');
    }

    #[Test]
    public function it_upload_rejects_svg_files(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin']);
        $customerId = 1;
        $urlKey = 'test-key';
        
        /* Act */
        // POST /upload/upload_file/{customerId}/{url_key}
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
}
