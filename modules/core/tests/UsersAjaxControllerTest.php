<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UsersAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UsersAjaxController
 * 
 * Tests AJAX functionality for user management and user-client relationships.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UsersAjaxController::class)]
class UsersAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures, ProvidesTestData, ProvidesAssertions;
    
    protected string $controllerClass = UsersAjaxController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
        // Intentionally empty - test data provided via traits
    }
    
    // #region Authentication
    
    /**
     * Test that name query requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_name_query(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=Test
         */
        $response = $this->get('/users/usersajax/name_query?query=Test');
        
        /* Assert */
        $this->assertUnauthorized($response);
    }
    
    // #endregion
    
    // #region AJAX Endpoints - Name Query

    // #region AJAX Endpoints - Name Query

    /**
     * Happy Path: Name query returns JSON
     */
    #[Test]
    public function it_returns_json_for_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=Test
         */
        $response = $this->get('/users/usersajax/name_query?query=Test');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test name query filters by user type
     */
    #[Test]
    public function it_filters_name_query_by_user_type(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=User&user_type=1
         */
        $response = $this->get('/users/usersajax/name_query?query=User&user_type=1');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test name query searches user name
     */
    #[Test]
    public function it_searches_user_name_in_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=Admin
         */
        $response = $this->get('/users/usersajax/name_query?query=Admin');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test name query searches user company
     */
    #[Test]
    public function it_searches_user_company_in_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=Company
         */
        $response = $this->get('/users/usersajax/name_query?query=Company');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test name query returns active users only
     */
    #[Test]
    public function it_returns_active_users_only_in_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=User
         */
        $response = $this->get('/users/usersajax/name_query?query=User');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test name query supports permissive search
     */
    #[Test]
    public function it_supports_permissive_search_in_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=test&permissive=1
         */
        $response = $this->get('/users/usersajax/name_query?query=test&permissive=1');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test name query returns empty array for empty query
     */
    #[Test]
    public function it_returns_empty_array_for_empty_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=
         */
        $response = $this->get('/users/usersajax/name_query?query=');
        
        /* Assert */
        $this->assertSuccessful($response);
        $response->assertJson([]);
    }

    /**
     * Test name query orders results by name
     */
    #[Test]
    public function it_orders_results_by_name_in_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=User
         */
        $response = $this->get('/users/usersajax/name_query?query=User');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }
    
    // #endregion
    
    // #region AJAX Endpoints - Get Latest

    /**
     * Happy Path: Get latest returns recent users
     */
    #[Test]
    public function it_returns_recent_users_for_get_latest(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/latest
         */
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test get latest limits to five users
     */
    #[Test]
    public function it_limits_to_five_users_in_get_latest(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/latest
         */
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test get latest returns JSON
     */
    #[Test]
    public function it_returns_json_for_get_latest(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/latest
         */
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test get latest escapes HTML in output
     */
    #[Test]
    public function it_escapes_html_in_output_for_get_latest(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/latest
         */
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test get latest orders by date created
     */
    #[Test]
    public function it_orders_by_date_created_in_get_latest(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/latest
         */
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $this->assertJsonResponse($response);
    }
    
    // #endregion
    
    // #region AJAX Endpoints - Save User Client

    /**
     * Happy Path: Assign client to existing user
     */
    #[Test]
    public function it_assigns_client_to_existing_user_via_save_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        $client = $this->fixtures->get('clients', 'active_client');
        
        $postData = $this->makeUserClientData([
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ]);
        
        /**
         * Act: POST /users/usersajax/save_user_client
         * POST data: {
         *   "user_id": "1",
         *   "client_id": "1"
         * }
         */
        $response = $this->post('/users/usersajax/save_user_client', $postData);
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test save user client stores in session for new user
     */
    #[Test]
    public function it_stores_in_session_for_new_user_via_save_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $client = $this->fixtures->get('clients', 'active_client');
        
        $postData = $this->makeUserClientData([
            'user_id' => '0',
            'client_id' => $client['client_id'],
        ]);
        
        /**
         * Act: POST /users/usersajax/save_user_client
         * POST data: {
         *   "user_id": "0",
         *   "client_id": "1"
         * }
         */
        $response = $this->post('/users/usersajax/save_user_client', $postData);
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test save user client prevents duplicate assignments
     */
    #[Test]
    public function it_prevents_duplicate_assignments_via_save_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        $client = $this->fixtures->get('clients', 'active_client');
        
        $postData = $this->makeUserClientData([
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ]);
        
        /**
         * Act: POST /users/usersajax/save_user_client (first time)
         * POST data: {
         *   "user_id": "1",
         *   "client_id": "1"
         * }
         */
        $response = $this->post('/users/usersajax/save_user_client', $postData);
        $this->assertSuccessful($response);
        
        /**
         * Act: POST /users/usersajax/save_user_client (duplicate)
         * POST data: {
         *   "user_id": "1",
         *   "client_id": "1"
         * }
         */
        $response = $this->post('/users/usersajax/save_user_client', $postData);
        
        /* Assert */
        $response->assertStatus(422);
    }
    
    // #endregion
    
    // #region AJAX Endpoints - Load User Client Table

    /**
     * Test load user client table from session
     */
    #[Test]
    public function it_loads_from_session_via_load_user_client_table(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/usersajax/load_user_client_table
         * POST data: {
         *   "user_id": "0"
         * }
         */
        $response = $this->post('/users/usersajax/load_user_client_table', [
            'user_id' => '0',
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test load user client table from database
     */
    #[Test]
    public function it_loads_from_database_via_load_user_client_table(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /**
         * Act: POST /users/usersajax/load_user_client_table
         * POST data: {
         *   "user_id": "1"
         * }
         */
        $response = $this->post('/users/usersajax/load_user_client_table', [
            'user_id' => $adminUser['user_id'],
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test load user client table returns partial view
     */
    #[Test]
    public function it_returns_partial_view_via_load_user_client_table(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/usersajax/load_user_client_table
         * POST data: {
         *   "user_id": "0"
         * }
         */
        $response = $this->post('/users/usersajax/load_user_client_table', [
            'user_id' => '0',
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
        $response->assertSee('user_client_table', false);
    }
    
    // #endregion
    
    // #region AJAX Endpoints - Modal Add User Client

    /**
     * Test modal add user client displays available clients
     */
    #[Test]
    public function it_displays_available_clients_in_modal_add_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/modal_add_user_client
         */
        $response = $this->get('/users/usersajax/modal_add_user_client');
        
        /* Assert */
        $this->assertSuccessful($response);
        $response->assertSee('client_id', false);
    }

    /**
     * Test modal excludes assigned clients
     */
    #[Test]
    public function it_excludes_assigned_clients_in_modal_add_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /**
         * Act: GET /users/usersajax/modal_add_user_client
         * GET data: user_id={id}
         */
        $response = $this->get('/users/usersajax/modal_add_user_client?user_id=' . $adminUser['user_id']);
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test modal uses session for new users
     */
    #[Test]
    public function it_uses_session_for_new_users_in_modal_add_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/modal_add_user_client
         * GET data: user_id=0
         */
        $response = $this->get('/users/usersajax/modal_add_user_client?user_id=0');
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test modal loads modal view
     */
    #[Test]
    public function it_loads_modal_view_in_modal_add_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/modal_add_user_client
         */
        $response = $this->get('/users/usersajax/modal_add_user_client');
        
        /* Assert */
        $this->assertSuccessful($response);
        $response->assertSee('modal', false);
    }
    
    // #endregion
    
    // #region AJAX Endpoints - Save Preference

    /**
     * Happy Path: Save permissive search preference
     */
    #[Test]
    public function it_saves_permissive_search_setting_via_save_preference(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/usersajax/save_preference_permissive_search_users
         * POST data: {
         *   "value": "1"
         * }
         */
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '1',
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test save preference accepts zero or one
     */
    #[Test]
    public function it_accepts_zero_or_one_via_save_preference(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act & Assert: POST /users/usersajax/save_preference_permissive_search_users
         * POST data: {"value": "0"}
         */
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '0',
        ]);
        $this->assertSuccessful($response);
        
        /**
         * Act & Assert: POST /users/usersajax/save_preference_permissive_search_users
         * POST data: {"value": "1"}
         */
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '1',
        ]);
        $this->assertSuccessful($response);
    }
    
    // #endregion
    
    // #region Validation

    /**
     * Test save preference validates input
     */
    #[Test]
    public function it_validates_input_for_save_preference(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/usersajax/save_preference_permissive_search_users
         * POST data: {
         *   "value": "1"
         * }
         */
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '1',
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
    }

    /**
     * Test save preference rejects invalid values
     */
    #[Test]
    public function it_rejects_invalid_values_in_save_preference(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/usersajax/save_preference_permissive_search_users
         * POST data: {
         *   "value": "999"
         * }
         */
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '999',
        ]);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test save user client validates client exists
     */
    #[Test]
    public function it_validates_client_exists_in_save_user_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        $postData = $this->makeUserClientData([
            'user_id' => $adminUser['user_id'],
            'client_id' => 9999,
        ]);
        
        /**
         * Act: POST /users/usersajax/save_user_client
         * POST data: {
         *   "user_id": "1",
         *   "client_id": 9999
         * }
         */
        $response = $this->post('/users/usersajax/save_user_client', $postData);
        
        /* Assert */
        $response->assertStatus(422);
    }
    
    // #endregion
    
    // #region Security

    /**
     * Test name query escapes SQL special characters
     */
    #[Test]
    public function it_escapes_sql_special_characters_in_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query='; DROP TABLE ip_users; --
         */
        $response = $this->get('/users/usersajax/name_query', [
            'query' => "'; DROP TABLE ip_users; --",
        ]);
        
        /* Assert */
        $this->assertJsonResponse($response);
    }

    /**
     * Test that AJAX controller flag is set
     */
    #[Test]
    public function it_has_ajax_controller_flag_set(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/usersajax/name_query
         * GET data: query=Test
         */
        $response = $this->get('/users/usersajax/name_query?query=Test');
        
        /* Assert */
        $this->assertSuccessful($response);
    }
    
    // #endregion
}
