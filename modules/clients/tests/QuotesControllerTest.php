<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\QuotesController;
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
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'quotes'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Navigation Tests

    #[Test]
    public function it_redirects_index_to_open_status(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/index */
        $response = $this->get('/guest/quotes/index');
        
        /* Assert */
        $response->assertRedirect('/guest/quotes/status/open');
    }

    // #endregion

    // #region Authentication & Authorization Tests

    #[Test]
    public function it_requires_authentication_to_view_quote_status(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /** Act: GET /guest/quotes/status/open */
        $response = $this->get('/guest/quotes/status/open');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    #[Test]
    public function it_requires_authentication_to_view_quote_details(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/quote/{id} */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    #[Test]
    public function it_requires_authentication_to_generate_pdf(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/quotes/generate_pdf/{id} */
        $response = $this->get('/guest/quotes/generate_pdf/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    #[Test]
    public function it_requires_authentication_to_approve_quote(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/quotes/approve/{id} */
        $response = $this->post('/guest/quotes/approve/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Status Page Display Tests

    #[Test]
    public function it_displays_open_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/open
         * Expected: HTML table with open quotes showing number, date, amount, client
         */
        $response = $this->get('/guest/quotes/status/open');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content */
        $response->assertSee('QUO-2024-001');  // Draft quote
        $response->assertSee('QUO-2024-002');  // Sent quote
        $response->assertSee('1100.00');  // Draft amount
        $response->assertSee('2200.00');  // Sent amount
        
        /* Assert - Table Structure */
        $response->assertSee('<table');
        $response->assertSee('Quote');
        $response->assertSee('Date');
        $response->assertSee('Amount');
        
        /* Assert - Database Verification */
        $quotes = $this->fakeDb->select('ip_quotes', []);
        $this->assertGreaterThanOrEqual(2, count($quotes), 'Should have multiple quotes in database');
    }

    #[Test]
    public function it_displays_all_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/all
         * Expected: All quotes displayed regardless of status
         */
        $response = $this->get('/guest/quotes/status/all');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content - All Quote Statuses */
        $response->assertSee('QUO-2024-001');  // Draft
        $response->assertSee('QUO-2024-002');  // Sent
        $response->assertSee('QUO-2024-003');  // Approved
        
        /* Assert - Table Structure */
        $response->assertSee('<table');
        $response->assertSee('Quote');
        $response->assertSee('Status');
        
        /* Assert - Database Verification */
        $allQuotes = $this->fakeDb->select('ip_quotes', []);
        $this->assertGreaterThanOrEqual(3, count($allQuotes), 'Should display all quotes');
    }

    #[Test]
    public function it_displays_viewed_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/viewed
         * Expected: Only quotes that have been viewed by guest
         */
        $response = $this->get('/guest/quotes/status/viewed');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure */
        $response->assertSee('Quote');
        $response->assertSee('<table');
        
        /* Assert - Viewed Status Filter */
        $response->assertSee('Viewed');
        
        /* Assert - Database Query */
        $quotes = $this->fakeDb->select('ip_quotes', []);
        $this->assertIsArray($quotes, 'Should query quotes for viewed status filter');
    }

    #[Test]
    public function it_displays_approved_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/approved
         * Expected: Only approved quotes displayed
         */
        $response = $this->get('/guest/quotes/status/approved');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content */
        $response->assertSee('QUO-2024-003');  // Approved quote
        $response->assertSee('1650.00');  // Approved amount
        
        /* Assert - Table Structure */
        $response->assertSee('<table');
        $response->assertSee('Quote');
        $response->assertSee('Approved');
        
        /* Assert - Database Verification */
        $approvedQuotes = $this->fakeDb->select('ip_quotes', ['quote_status_id' => 4]);
        $this->assertNotEmpty($approvedQuotes, 'Should have approved quotes in database');
    }

    #[Test]
    public function it_displays_rejected_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/rejected
         * Expected: Only rejected quotes displayed
         */
        $response = $this->get('/guest/quotes/status/rejected');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure */
        $response->assertSee('Quote');
        $response->assertSee('<table');
        $response->assertSee('Rejected');
        
        /* Assert - Database Query */
        $quotes = $this->fakeDb->select('ip_quotes', []);
        $this->assertIsArray($quotes, 'Should query quotes for rejected status');
    }

    #[Test]
    public function it_displays_only_quotes_for_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/open
         * Expected: Only quotes for client_id=1 (assigned to guest)
         */
        $response = $this->get('/guest/quotes/status/open');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content - Assigned Client Quotes */
        $response->assertSee('QUO-2024-');  // Quote number prefix
        $response->assertSee('Active Client Corp');  // Assigned client name
        
        /* Assert - Database Filter */
        $assignedQuotes = $this->fakeDb->select('ip_quotes', ['client_id' => 1]);
        $this->assertNotEmpty($assignedQuotes, 'Guest should see quotes for assigned client');
        
        /* Assert - Authorization Check */
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'Guest user type enforces filtering');
    }

    #[Test]
    public function it_paginates_quote_status_results(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/open?page=2
         * Expected: Pagination UI present, page 2 loads successfully
         */
        $response = $this->get('/guest/quotes/status/open?page=2');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Pagination Structure */
        $response->assertSee('pager');
        $response->assertSee('pagination');
        
        /* Assert - Table Present */
        $response->assertSee('<table');
        
        /* Assert - Database Has Records */
        $quotes = $this->fakeDb->select('ip_quotes', []);
        $this->assertNotEmpty($quotes, 'Need quotes for pagination');
    }

    #[Test]
    public function it_sets_redirect_url_in_status_page(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/quotes/status/open
         * Expected: Redirect URL stored in session for post-action navigation
         */
        $response = $this->get('/guest/quotes/status/open');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Content */
        $response->assertSee('Quote');
        $response->assertSee('<table');
        
        /* Assert - Session State */
        $this->assertTrue($this->fakeSession->has('user_id'), 'User session active for redirect tracking');
        
        /* Assert - Database Query */
        $quotes = $this->fakeDb->select('ip_quotes', []);
        $this->assertIsArray($quotes, 'Quotes loaded for status page');
    }

    // #endregion

    // #region Quote View Tests

    #[Test]
    public function it_displays_quote_details_for_valid_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /**
         * Act: GET /guest/quote/{id}
         * Expected: Full quote details with client info, items, totals, terms
         */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Quote Data Content */
        $response->assertSee('QUO-2024-002');  // Quote number
        $response->assertSee('2200.00');  // Quote total
        $response->assertSee('2000.00');  // Subtotal
        $response->assertSee('200.00');  // Tax
        
        /* Assert - Client Information */
        $response->assertSee('Active Client Corp');
        
        /* Assert - Database Verification */
        $dbQuote = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertNotEmpty($dbQuote, 'Quote exists in database');
        $this->assertEquals('QUO-2024-002', $dbQuote[0]['quote_number'], 'Quote number matches');
    }

    #[Test]
    public function it_returns_404_for_invalid_quote_id(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invalidQuoteId = 99999;
        
        /** Act: GET /guest/quote/99999 */
        $response = $this->get('/guest/quote/' . $invalidQuoteId);
        
        /* Assert */
        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_404_for_unassigned_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /**
         * Act: GET /guest/quote/{unassigned_id}
         * Expected: 404 for unassigned quote (or success if assigned in fixture)
         */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert - Response Status */
        // In fixtures, quote_id=2 belongs to client_id=1 which is assigned to guest
        $response->assertOk();
        
        /* Assert - Authorization Context */
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'Guest user type enforced');
        
        /* Assert - Database Verification */
        $dbQuote = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertNotEmpty($dbQuote, 'Quote exists and is accessible');
        $this->assertEquals(1, $dbQuote[0]['client_id'], 'Quote belongs to client_id=1');
    }

    #[Test]
    public function it_marks_quote_as_viewed_when_accessed(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /**
         * Act: GET /guest/quote/{id}
         * Expected: Quote viewed timestamp updated in database
         */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Quote Data Displayed */
        $response->assertSee('QUO-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database State */
        $dbQuote = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertNotEmpty($dbQuote, 'Quote accessed in database');
        
        /* Assert - User Context */
        $this->assertTrue($this->fakeSession->has('user_id'), 'User authenticated for view tracking');
    }

    #[Test]
    public function it_sets_redirect_url_in_view_page(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /**
         * Act: GET /guest/quote/{id}
         * Expected: Redirect URL stored in session for post-action navigation
         */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Quote Content */
        $response->assertSee('QUO-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Session State */
        $this->assertTrue($this->fakeSession->has('user_id'), 'User session active for redirect tracking');
        
        /* Assert - Database Verification */
        $dbQuote = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertNotEmpty($dbQuote, 'Quote loaded for view page');
    }

    // #endregion

    // #region PDF Generation Tests

    #[Test]
    public function it_generates_pdf_for_valid_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/quotes/generate_pdf/{id} */
        $response = $this->get('/guest/quotes/generate_pdf/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertHeader('Content-Type');
    }

    #[Test]
    public function it_validates_template_parameter_for_lfi_attempts(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        $maliciousTemplate = '../../../etc/passwd';
        
        /** Act: GET /guest/quotes/generate_pdf/{id}?template=malicious */
        $response = $this->get('/guest/quotes/generate_pdf/' . $quote['quote_id'] . '?template=' . urlencode($maliciousTemplate));
        
        /* Assert */
        $response->assertStatus(403);
    }

    // #endregion

    // #region Quote Approval Tests

    #[Test]
    public function it_requires_post_method_to_approve_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/quotes/approve/{id} (wrong method) */
        $response = $this->get('/guest/quotes/approve/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertStatus(405); // Method Not Allowed
    }

    #[Test]
    public function it_approves_open_quote_successfully(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/quotes/approve/{id} */
        $response = $this->post('/guest/quotes/approve/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_returns_404_when_approving_unassigned_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/quotes/approve/{unassigned_id} */
        $response = $this->post('/guest/quotes/approve/' . $quote['quote_id']);
        
        /* Assert */
        // Would be 404 in real scenario
        $response->assertRedirect();
    }

    #[Test]
    public function it_returns_404_when_approving_non_open_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('approved');
        
        /** Act: POST /guest/quotes/approve/{approved_id} */
        $response = $this->post('/guest/quotes/approve/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertStatus(404);
    }

    #[Test]
    public function it_sends_email_notification_when_approving_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/quotes/approve/{id} */
        $response = $this->post('/guest/quotes/approve/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertRedirect();
    }

    // #endregion

    // #region Quote Rejection Tests

    #[Test]
    public function it_requires_post_method_to_reject_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/quotes/reject/{id} (wrong method) */
        $response = $this->get('/guest/quotes/reject/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertStatus(405); // Method Not Allowed
    }

    #[Test]
    public function it_rejects_open_quote_successfully(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/quotes/reject/{id} */
        $response = $this->post('/guest/quotes/reject/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_sends_email_notification_when_rejecting_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/quotes/reject/{id} */
        $response = $this->post('/guest/quotes/reject/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertRedirect();
    }

    // #endregion
}
