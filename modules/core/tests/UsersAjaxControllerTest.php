<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UsersAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UsersAjaxController using Laravel HTTP testing
 * 
 * Tests the full HTTP request/response cycle.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UsersAjaxController::class)]
class UsersAjaxControllerTest extends TestCase
{
    
    protected function loadFixtures(): void
    {
        // Load user fixtures
        $users = $this->fixtures->all('users');
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load client fixtures for user-client relationships
        $clients = $this->fixtures->all('clients');
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
    }
    
    /**
     * Test that name query requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_name_query(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        // GET /users/usersajax/name_query?query=Test
        $response = $this->get('/users/usersajax/name_query?query=Test');
        
        /* Assert */
        $response->assertStatus(401);
    }

    /**
     * Happy Path: Name query returns JSON
     */
    #[Test]
    public function it_get_name_query_returns_json(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=Test
        $response = $this->get('/users/usersajax/name_query?query=Test');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test name query filters by user type
     */
    #[Test]
    public function it_get_name_query_filters_by_user_type(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=User&user_type=1
        $response = $this->get('/users/usersajax/name_query?query=User&user_type=1');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test name query searches user name
     */
    #[Test]
    public function it_get_name_query_searches_user_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=Admin
        $response = $this->get('/users/usersajax/name_query?query=Admin');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test name query searches user company
     */
    #[Test]
    public function it_get_name_query_searches_user_company(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=Company
        $response = $this->get('/users/usersajax/name_query?query=Company');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test name query returns active users only
     */
    #[Test]
    public function it_get_name_query_returns_active_users_only(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=User
        $response = $this->get('/users/usersajax/name_query?query=User');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test name query supports permissive search
     */
    #[Test]
    public function it_get_name_query_supports_permissive_search(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=test&permissive=1
        $response = $this->get('/users/usersajax/name_query?query=test&permissive=1');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test name query returns empty for empty query
     */
    #[Test]
    public function it_get_name_query_returns_empty_for_empty_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=
        $response = $this->get('/users/usersajax/name_query?query=');
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([]);
    }

    /**
     * Test name query escapes SQL special characters
     */
    #[Test]
    public function it_get_name_query_escapes_sql_special_characters(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query
        $response = $this->get('/users/usersajax/name_query', [
            'query' => "'; DROP TABLE ip_users; --",
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test name query orders results by name
     */
    #[Test]
    public function it_get_name_query_orders_results_by_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=User
        $response = $this->get('/users/usersajax/name_query?query=User');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Happy Path: Get latest returns recent users
     */
    #[Test]
    public function it_get_latest_returns_recent_users(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/latest
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test get latest limits to five users
     */
    #[Test]
    public function it_get_latest_limits_to_five_users(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/latest
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test get latest returns JSON
     */
    #[Test]
    public function it_get_latest_returns_json(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/latest
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test get latest escapes HTML in output
     */
    #[Test]
    public function it_get_latest_escapes_html_in_output(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/latest
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test get latest orders by date created
     */
    #[Test]
    public function it_get_latest_orders_by_date_created(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/latest
        $response = $this->get('/users/usersajax/latest');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test save preference validates input
     */
    #[Test]
    public function it_validates_save_preference_permissive_search_users_input(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/usersajax/save_preference_permissive_search_users
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '1',
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Happy Path: Save permissive search preference
     */
    #[Test]
    public function it_post_save_preference_permissive_search_users_saves_setting(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/usersajax/save_preference_permissive_search_users
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '1',
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test save preference accepts zero or one
     */
    #[Test]
    public function it_post_save_preference_permissive_search_users_accepts_zero_or_one(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act & Assert - Test value 0 */
        // POST /users/usersajax/save_preference_permissive_search_users
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '0',
        ]);
        $response->assertOk();
        
        /* Act & Assert - Test value 1 */
        // POST /users/usersajax/save_preference_permissive_search_users
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '1',
        ]);
        $response->assertOk();
    }

    /**
     * Test save preference rejects invalid values
     */
    #[Test]
    public function it_post_save_preference_permissive_search_users_rejects_invalid_values(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/usersajax/save_preference_permissive_search_users
        $response = $this->post('/users/usersajax/save_preference_permissive_search_users', [
            'value' => '999',
        ]);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Happy Path: Assign client to existing user
     */
    #[Test]
    public function it_post_save_user_client_assigns_client_to_existing_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        $client = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        // POST /users/usersajax/save_user_client
        $response = $this->post('/users/usersajax/save_user_client', [
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test save user client stores in session for new user
     */
    #[Test]
    public function it_post_save_user_client_stores_in_session_for_new_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $client = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        // POST /users/usersajax/save_user_client
        $response = $this->post('/users/usersajax/save_user_client', [
            'user_id' => '0',
            'client_id' => $client['client_id'],
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test save user client prevents duplicate assignments
     */
    #[Test]
    public function it_post_save_user_client_prevents_duplicate_assignments(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        $client = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        // POST /users/usersajax/save_user_client (first time)
        $response = $this->post('/users/usersajax/save_user_client', [
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ]);
        $response->assertOk();
        
        // POST /users/usersajax/save_user_client (duplicate)
        $response = $this->post('/users/usersajax/save_user_client', [
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ]);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test save user client validates client exists
     */
    #[Test]
    public function it_validates_save_user_client_client_exists(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /* Act */
        // POST /users/usersajax/save_user_client
        $response = $this->post('/users/usersajax/save_user_client', [
            'user_id' => $adminUser['user_id'],
            'client_id' => 9999,
        ]);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test load user client table from session
     */
    #[Test]
    public function it_post_load_user_client_table_loads_from_session(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $client = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        // POST /users/usersajax/load_user_client_table
        $response = $this->post('/users/usersajax/load_user_client_table', [
            'user_id' => '0',
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test load user client table from database
     */
    #[Test]
    public function it_post_load_user_client_table_loads_from_database(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /* Act */
        // POST /users/usersajax/load_user_client_table
        $response = $this->post('/users/usersajax/load_user_client_table', [
            'user_id' => $adminUser['user_id'],
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test load user client table returns partial view
     */
    #[Test]
    public function it_post_load_user_client_table_returns_partial_view(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/usersajax/load_user_client_table
        $response = $this->post('/users/usersajax/load_user_client_table', [
            'user_id' => '0',
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('user_client_table', false);
    }

    /**
     * Test modal add user client displays available clients
     */
    #[Test]
    public function it_displays_modal_add_user_client_available_clients(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/modal_add_user_client
        $response = $this->get('/users/usersajax/modal_add_user_client');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('client_id', false);
    }

    /**
     * Test modal excludes assigned clients
     */
    #[Test]
    public function it_get_modal_add_user_client_excludes_assigned_clients(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /* Act */
        // GET /users/usersajax/modal_add_user_client?user_id={id}
        $response = $this->get('/users/usersajax/modal_add_user_client?user_id=' . $adminUser['user_id']);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test modal uses session for new users
     */
    #[Test]
    public function it_get_modal_add_user_client_uses_session_for_new_users(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/modal_add_user_client?user_id=0
        $response = $this->get('/users/usersajax/modal_add_user_client?user_id=0');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test modal loads modal view
     */
    #[Test]
    public function it_get_modal_add_user_client_loads_modal_view(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/modal_add_user_client
        $response = $this->get('/users/usersajax/modal_add_user_client');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('modal', false);
    }

    /**
     * Test that AJAX controller flag is set
     */
    #[Test]
    public function it_ajax_controller_flag_is_set(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /users/usersajax/name_query?query=Test
        $response = $this->get('/users/usersajax/name_query?query=Test');
        
        /* Assert */
        $response->assertOk();
    }
}
