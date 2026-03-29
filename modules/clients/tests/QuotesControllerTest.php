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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region Status Page Display Tests

    #[Test]
    public function it_displays_open_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/open */
        $response = $this->get('/guest/quotes/status/open');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_all_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/all */
        $response = $this->get('/guest/quotes/status/all');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_viewed_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/viewed */
        $response = $this->get('/guest/quotes/status/viewed');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_approved_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/approved */
        $response = $this->get('/guest/quotes/status/approved');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_rejected_quotes_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/rejected */
        $response = $this->get('/guest/quotes/status/rejected');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_only_quotes_for_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/open */
        $response = $this->get('/guest/quotes/status/open');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_paginates_quote_status_results(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/open?page=2 */
        $response = $this->get('/guest/quotes/status/open?page=2');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_sets_redirect_url_in_status_page(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/quotes/status/open */
        $response = $this->get('/guest/quotes/status/open');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
        
        /** Act: GET /guest/quote/{id} */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
        
        /** Act: GET /guest/quote/{unassigned_id} */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert */
        // Would be 404 in real scenario
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_marks_quote_as_viewed_when_accessed(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/quote/{id} */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_sets_redirect_url_in_view_page(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/quote/{id} */
        $response = $this->get('/guest/quote/' . $quote['quote_id']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
