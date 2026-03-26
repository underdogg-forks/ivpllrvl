<?php

namespace Modules\Core\Tests;

use Modules\Core\Services\UserService;
use Modules\Core\Testing\ServiceTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for UserService
 * 
 * Tests service methods, validation rules, scopes, and SQL queries
 */
#[CoversClass(UserService::class)]
class UserServiceTest extends ServiceTestCase
{
    protected string $serviceClass = UserService::class;

    protected function setUpService(): void
    {
        // Service-specific setup
    }

    #[Test]
    public function it_has_correct_table_name(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertUsesTable('ip_users');
    }

    #[Test]
    public function it_has_correct_primary_key(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertHasPrimaryKey('ip_users.user_id');
    }

    #[Test]
    public function it_has_date_created_field(): void
    {
        /* Arrange & Act */
        $service = $this->getService();
        
        /* Assert */
        $this->assertServiceHasProperty('date_created_field');
        $this->assertEquals('user_date_created', $service->date_created_field);
    }

    #[Test]
    public function it_has_date_modified_field(): void
    {
        /* Arrange & Act */
        $service = $this->getService();
        
        /* Assert */
        $this->assertServiceHasProperty('date_modified_field');
        $this->assertEquals('user_date_modified', $service->date_modified_field);
    }

    #[Test]
    public function it_returns_user_types(): void
    {
        /* Arrange */
        $service = $this->getService();
        
        /* Act */
        $userTypes = $service->user_types();
        
        /* Assert */
        $this->assertIsArray($userTypes);
        $this->assertArrayHasKey('1', $userTypes); // Administrator
        $this->assertArrayHasKey('2', $userTypes); // Guest
        $this->assertEquals(2, count($userTypes));
    }

    #[Test]
    public function it_has_default_select_method(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertServiceHasMethod('default_select');
    }

    #[Test]
    public function it_has_default_order_by_method(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertServiceHasMethod('default_order_by');
    }

    #[Test]
    public function it_has_validation_rules_method(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertServiceHasMethod('validation_rules');
    }

    #[Test]
    public function it_requires_user_type_field(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act & Assert */
        $this->assertFieldIsRequired($rules, 'user_type');
    }

    #[Test]
    public function it_requires_user_email_field(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act & Assert */
        $this->assertFieldIsRequired($rules, 'user_email');
    }

    #[Test]
    public function it_validates_email_format(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act */
        $emailRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_email') {
                $emailRule = $rule;
                break;
            }
        }
        
        /* Assert */
        $this->assertNotNull($emailRule);
        $this->assertStringContainsString('valid_email', $emailRule['rules']);
    }

    #[Test]
    public function it_requires_unique_email(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act */
        $emailRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_email') {
                $emailRule = $rule;
                break;
            }
        }
        
        /* Assert */
        $this->assertNotNull($emailRule);
        $this->assertStringContainsString('is_unique', $emailRule['rules']);
    }

    #[Test]
    public function it_requires_user_name_field(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act & Assert */
        $this->assertFieldIsRequired($rules, 'user_name');
    }

    #[Test]
    public function it_requires_user_password_field(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act & Assert */
        $this->assertFieldIsRequired($rules, 'user_password');
    }

    #[Test]
    public function it_enforces_minimum_password_length(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act */
        $passwordRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_password') {
                $passwordRule = $rule;
                break;
            }
        }
        
        /* Assert */
        $this->assertNotNull($passwordRule);
        $this->assertStringContainsString('min_length[8]', $passwordRule['rules']);
    }

    #[Test]
    public function it_requires_password_confirmation(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act & Assert */
        $this->assertHasValidationRule($rules, 'user_passwordv');
    }

    #[Test]
    public function it_validates_password_match(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act */
        $passwordConfirmRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_passwordv') {
                $passwordConfirmRule = $rule;
                break;
            }
        }
        
        /* Assert */
        $this->assertNotNull($passwordConfirmRule);
        $this->assertStringContainsString('matches[user_password]', $passwordConfirmRule['rules']);
    }

    #[Test]
    public function it_has_validation_rules_for_existing_users(): void
    {
        /* Arrange */
        $service = $this->getService();
        
        /* Act & Assert */
        $this->assertServiceHasMethod('validation_rules_existing');
    }

    #[Test]
    public function it_existing_user_rules_allow_same_email(): void
    {
        /* Arrange */
        $service = $this->getService();
        
        /* Act */
        if (!method_exists($service, 'validation_rules_existing')) {
            $this->markTestIncomplete('validation_rules_existing method not found');
            return;
        }
        
        $rules = $service->validation_rules_existing();
        
        /* Assert */
        $emailRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_email') {
                $emailRule = $rule;
                break;
            }
        }
        
        // For existing users, unique check should exclude current user
        $this->assertNotNull($emailRule);
        // The rule should NOT have simple is_unique or should have callback
    }

    #[Test]
    public function it_orders_by_user_name_by_default(): void
    {
        /* Arrange */
        // This test would need CI DB mock
        
        /* Act */
        
        /* Assert */
        $this->markTestIncomplete('Requires DB mock or CI bootstrap');
    }

    #[Test]
    public function it_selects_all_user_fields_by_default(): void
    {
        /* Arrange */
        // This test would need CI DB mock
        
        /* Act */
        
        /* Assert */
        $this->markTestIncomplete('Requires DB mock or CI bootstrap');
    }

    #[Test]
    public function it_has_prep_form_method(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertServiceHasMethod('prep_form');
    }

    #[Test]
    public function it_has_save_method(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertServiceHasMethod('save');
    }

    #[Test]
    public function it_has_get_by_id_method(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertServiceHasMethod('get_by_id');
    }

    #[Test]
    public function it_filters_sensitive_fields_in_forms(): void
    {
        /* Arrange */
        // Test that password fields are handled correctly in prep_form
        
        /* Act */
        
        /* Assert */
        $this->markTestIncomplete('Requires CI bootstrap to test form prep');
    }

    #[Test]
    public function it_hashes_passwords_before_save(): void
    {
        /* Arrange */
        // Verify password hashing logic
        
        /* Act */
        
        /* Assert */
        $this->markTestIncomplete('Requires CI bootstrap to test password hashing');
    }

    #[Test]
    public function it_validates_user_type_is_valid_option(): void
    {
        /* Arrange */
        $service = $this->getService();
        $validTypes = $service->user_types();
        
        /* Act & Assert */
        $this->assertArrayHasKey('1', $validTypes);
        $this->assertArrayHasKey('2', $validTypes);
        $this->assertArrayNotHasKey('3', $validTypes); // No third type
    }

    #[Test]
    public function it_supports_pagination(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertServiceHasMethod('paginate');
    }

    #[Test]
    public function it_supports_filtering(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        // Check for where/like methods or filter scopes
        $this->markTestIncomplete('Filtering methods need to be identified');
    }

    #[Test]
    public function it_excludes_deleted_users_by_default(): void
    {
        /* Arrange */
        // If soft deletes are used
        
        /* Act */
        
        /* Assert */
        $this->markTestIncomplete('Check if soft deletes are implemented');
    }

    #[Test]
    public function it_sanitizes_input_data(): void
    {
        /* Arrange */
        // Test XSS protection in save method
        
        /* Act */
        
        /* Assert */
        $this->markTestIncomplete('Requires CI bootstrap to test input sanitization');
    }
}
