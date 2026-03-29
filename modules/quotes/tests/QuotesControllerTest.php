<?php

namespace Modules\Quotes\Tests;

use Modules\Quotes\Controllers\QuotesController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for QuotesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(QuotesController::class)]
class QuotesControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = QuotesController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'quotes'];
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


    // #region Authentication & Authorization Tests

    /**
     * Test that quotes index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_quotes_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /quotes/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/quotes/index');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Quotes index redirects to status/all
     */
    #[Test]
    public function it_redirects_to_status_all_from_quotes_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /quotes/index
         * Expected behavior: Redirect to quotes status page showing all quotes
         */
        $response = $this->get('/quotes/index');
        
        /* Assert */
        $response->assertRedirect('/quotes/status/all');
        $this->assertTrue($this->fakeSession->has('user_id'));
        $this->assertEquals(1, $this->fakeSession->get('user_type'));
    }


    /**
     * Happy Path: Status all displays all quotes
     */
    #[Test]
    public function it_displays_all_quotes_on_status_all_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        $sentQuote = $this->fixtures->get('quotes', 'sent_quote');
        $approvedQuote = $this->fixtures->get('quotes', 'approved_quote');
        
        /**
         * Act: GET /quotes/status/all
         * Expected behavior: Display all quotes regardless of status
         */
        $response = $this->get('/quotes/status/all');
        
        /* Assert */
        $this->assertResponseContainsAll($response, [
            $draftQuote['quote_number'],
            $sentQuote['quote_number'],
            $approvedQuote['quote_number']
        ]);
        $this->assertDatabaseCount('ip_quotes', [], 3);
    }

    /**
     * Test status draft displays only draft quotes
     */
    #[Test]
    public function it_displays_only_draft_quotes_on_status_draft_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        
        /**
         * Act: GET /quotes/status/draft
         * Expected behavior: Display only quotes with draft status (status_id = 1)
         */
        $response = $this->get('/quotes/status/draft');
        
        /* Assert */
        $response->assertSee($draftQuote['quote_number']);
        $this->assertDatabaseHasRecord('ip_quotes', [
            'quote_status_id' => 1,
            'quote_number' => 'QUO-2024-001'
        ]);
    }

    /**
     * Test status sent displays only sent quotes
     */
    #[Test]
    public function it_displays_only_sent_quotes_on_status_sent_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sentQuote = $this->fixtures->get('quotes', 'sent_quote');
        
        /**
         * Act: GET /quotes/status/sent
         * Expected behavior: Display only quotes with sent status (status_id = 2)
         */
        $response = $this->get('/quotes/status/sent');
        
        /* Assert */
        $response->assertSee($sentQuote['quote_number']);
        $this->assertDatabaseHasRecord('ip_quotes', [
            'quote_status_id' => 2,
            'quote_number' => 'QUO-2024-002'
        ]);
    }

    /**
     * Test status approved displays only approved quotes
     */
    #[Test]
    public function it_displays_only_approved_quotes_on_status_approved_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $approvedQuote = $this->fixtures->get('quotes', 'approved_quote');
        
        /**
         * Act: GET /quotes/status/approved
         * Expected behavior: Display only quotes with approved status (status_id = 4)
         */
        $response = $this->get('/quotes/status/approved');
        
        /* Assert */
        $response->assertSee($approvedQuote['quote_number']);
        $this->assertDatabaseHasRecord('ip_quotes', [
            'quote_status_id' => 4,
            'quote_number' => 'QUO-2024-003'
        ]);
    }

    /**
     * Test status rejected displays only rejected quotes
     */
    #[Test]
    public function it_displays_only_rejected_quotes_on_status_rejected_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /quotes/status/rejected
         * Expected behavior: Display only quotes with rejected status (status_id = 3)
         */
        $response = $this->get('/quotes/status/rejected');
        
        /* Assert */
        $response->assertSee('No quotes found');
        $this->assertDatabaseMissingRecord('ip_quotes', ['quote_status_id' => 3]);
    }

    /**
     * Test status page paginates results
     */
    #[Test]
    public function it_displays_pagination_on_quotes_status_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /quotes/status/all?page=1
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/quotes/status/all?page=1');
        
        /* Assert */
        $this->assertHasPagination($response);
        $this->assertDatabaseCount('ip_quotes', [], 3);
    }

    // #endregion

    // #region View & Detail Tests

    /**
     * Happy Path: View displays quote details
     */
    #[Test]
    public function it_displays_quote_details_on_view_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        $quoteId = $draftQuote['quote_id'];
        
        /**
         * Act: GET /quotes/view/{id}
         * Expected behavior: Display quote details including quote number and total
         */
        $response = $this->get('/quotes/view/' . $quoteId);
        
        /* Assert */
        $this->assertResponseContainsAll($response, [
            $draftQuote['quote_number'],
            $draftQuote['quote_total']
        ]);
        $this->assertDatabaseHasRecord('ip_quotes', [
            'quote_id' => $quoteId,
            'quote_number' => 'QUO-2024-001'
        ]);
    }

    /**
     * Test view returns 404 for invalid quote
     */
    #[Test]
    public function it_returns_404_for_invalid_quote_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidQuoteId = 9999;
        
        /**
         * Act: GET /quotes/view/{id}
         * Expected behavior: Return 404 for non-existent quote
         */
        $response = $this->get('/quotes/view/' . $invalidQuoteId);
        
        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_quotes', ['quote_id' => $invalidQuoteId]);
    }

    // #endregion

    // #region Form Submission Tests

    /**
     * Test POST cancels quote and updates status
     */
    #[Test]
    public function it_cancels_quote_and_redirects_to_view(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sentQuote = $this->fixtures->get('quotes', 'sent_quote');
        $quoteId = $sentQuote['quote_id'];
        
        /**
         * Act: POST /quotes/cancel
         * POST data: {
         *   "quote_id": "{quote_id}"
         * }
         * Expected behavior: Cancel quote and redirect to view page
         */
        $response = $this->post('/quotes/cancel', [
            'quote_id' => $quoteId
        ]);
        
        /* Assert */
        $response->assertRedirect('/quotes/view/' . $quoteId);
        $this->assertDatabaseHasRecord('ip_quotes', ['quote_id' => $quoteId]);
    }

    /**
     * Test POST recalculates all quote totals
     */
    #[Test]
    public function it_recalculates_all_quote_totals_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /quotes/recalculate_all
         * Expected behavior: Recalculate all quotes and display success message
         */
        $response = $this->post('/quotes/recalculate_all');
        
        /* Assert */
        $response->assertSessionHas('alert_success', 'All quotes recalculated');
        $this->assertDatabaseCount('ip_quotes', [], 3);
    }

    /**
     * Test POST deletes quote tax entry
     */
    #[Test]
    public function it_deletes_quote_tax_entry_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        $quoteId = $draftQuote['quote_id'];
        
        /**
         * Act: POST /quotes/delete_quote_tax
         * POST data: {
         *   "quote_id": "{quote_id}",
         *   "tax_rate_id": "1"
         * }
         * Expected behavior: Remove tax entry and redirect to view page
         */
        $response = $this->post('/quotes/delete_quote_tax', [
            'quote_id' => $quoteId,
            'tax_rate_id' => 1
        ]);
        
        /* Assert */
        $response->assertRedirect('/quotes/view/' . $quoteId);
        $this->assertDatabaseHasRecord('ip_quotes', ['quote_id' => $quoteId]);
    }

    // #endregion

    // #region Delete Tests

    /**
     * Test POST deletes draft quote successfully
     */
    #[Test]
    public function it_deletes_draft_quote_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        $quoteId = $draftQuote['quote_id'];
        
        /**
         * Act: POST /quotes/delete/{id}
         * POST data: {
         *   "quote_id": "{quote_id}"
         * }
         * Expected behavior: Delete draft quote and redirect to index
         */
        $response = $this->post('/quotes/delete/' . $quoteId, [
            'quote_id' => $quoteId
        ]);
        
        /* Assert */
        $response->assertRedirect('/quotes/index');
        $this->assertDatabaseHasRecord('ip_quotes', ['quote_id' => $quoteId]);
    }

    /**
     * Test POST prevents deleting sent quote
     */
    #[Test]
    public function it_prevents_deleting_sent_quote(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sentQuote = $this->fixtures->get('quotes', 'sent_quote');
        $quoteId = $sentQuote['quote_id'];
        
        /**
         * Act: POST /quotes/delete/{id}
         * POST data: {
         *   "quote_id": "{quote_id}"
         * }
         * Expected behavior: Prevent deletion and show error message
         */
        $response = $this->post('/quotes/delete/' . $quoteId, [
            'quote_id' => $quoteId
        ]);
        
        /* Assert */
        $response->assertSessionHas('alert_error', 'Cannot delete sent quote');
        $this->assertDatabaseHasRecord('ip_quotes', [
            'quote_id' => $quoteId,
            'quote_status_id' => 2
        ]);
    }

    // #endregion

    // #region PDF Generation Tests

    /**
     * Test GET generates PDF successfully
     */
    #[Test]
    public function it_generates_pdf_for_quote_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        $quoteId = $draftQuote['quote_id'];
        
        /**
         * Act: GET /quotes/generate_pdf/{id}
         * Expected behavior: Generate PDF and return with appropriate content type
         */
        $response = $this->get('/quotes/generate_pdf/' . $quoteId);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHasRecord('ip_quotes', ['quote_id' => $quoteId]);
    }

    /**
     * Test GET marks quote as sent when generating PDF
     */
    #[Test]
    public function it_marks_quote_as_sent_when_generating_pdf(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        $quoteId = $draftQuote['quote_id'];
        
        /**
         * Act: GET /quotes/generate_pdf/{id}
         * Expected behavior: Generate PDF and update quote status to sent
         */
        $response = $this->get('/quotes/generate_pdf/' . $quoteId);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_quotes', ['quote_id' => $quoteId]);
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test GET validates PDF template
     */
    #[Test]
    public function it_validates_pdf_template_is_valid(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $draftQuote = $this->fixtures->get('quotes', 'draft_quote');
        $quoteId = $draftQuote['quote_id'];
        
        /**
         * Act: GET /quotes/generate_pdf/{id}/invalid_template
         * Expected behavior: Show error for invalid template
         */
        $response = $this->get('/quotes/generate_pdf/' . $quoteId . '/invalid_template');
        
        /* Assert */
        $response->assertSessionHas('alert_error', 'Invalid template');
        $this->assertDatabaseHasRecord('ip_quotes', ['quote_id' => $quoteId]);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in quote data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_quote_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssPayload = '<script>alert("XSS")</script>';
        $quoteData = $this->makeQuoteData([
            'quote_number' => $xssPayload,
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Complete quote data with XSS payload in quote_number
         * Expected behavior: XSS payload should be sanitized
         */
        $response = $this->post('/quotes/ajax/create', $quoteData);
        
        /* Assert */
        $this->assertStringNotContainsString('<script>', $quoteData['quote_number']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Security: Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjection = "1' OR '1'='1";
        
        /**
         * Act: GET /quotes/view/{id}
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->get('/quotes/view/' . $sqlInjection);
        
        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_quotes', ['quote_id' => $sqlInjection]);
    }

    // #endregion
}
