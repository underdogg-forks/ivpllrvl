<?php

namespace Modules\Core\Testing\Traits;

/**
 * ProvidesAssertions Trait
 * 
 * Provides comprehensive assertion helpers beyond basic ->ok() checks.
 * Follows the principle: "Test behavior, not just status codes".
 */
trait ProvidesAssertions
{
    /**
     * Assert that a response redirects to login page (unauthenticated)
     */
    protected function assertRequiresAuthentication(mixed $response): void
    {
        $response->assertRedirect('/sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'), 'Session should not have user_id');
    }
    
    /**
     * Assert that a response redirects to dashboard (unauthorized)
     */
    protected function assertRequiresAuthorization(mixed $response): void
    {
        $response->assertRedirect('/dashboard');
        $this->assertTrue($this->fakeSession->has('user_id'), 'Session should have user_id');
    }
    
    /**
     * Assert that a validation error was set in flash data
     */
    protected function assertValidationError(string $expectedMessage = ''): void
    {
        $this->assertTrue(
            $this->fakeSession->hasFlash('alert_error'),
            'Flash data should contain alert_error'
        );
        
        if ($expectedMessage !== '') {
            $actualMessage = $this->fakeSession->getFlash('alert_error');
            $this->assertStringContainsString(
                $expectedMessage,
                $actualMessage,
                "Validation error should contain '{$expectedMessage}'"
            );
        }
    }
    
    /**
     * Assert that a success message was set in flash data
     */
    protected function assertSuccessMessage(string $expectedMessage = ''): void
    {
        $this->assertTrue(
            $this->fakeSession->hasFlash('alert_success'),
            'Flash data should contain alert_success'
        );
        
        if ($expectedMessage !== '') {
            $actualMessage = $this->fakeSession->getFlash('alert_success');
            $this->assertStringContainsString(
                $expectedMessage,
                $actualMessage,
                "Success message should contain '{$expectedMessage}'"
            );
        }
    }
    
    /**
     * Assert that a record exists in the database with given conditions
     * 
     * @param string $table Database table name
     * @param array<string, mixed> $conditions Field => value conditions
     */
    protected function assertDatabaseHasRecord(string $table, array $conditions): void
    {
        $records = $this->fakeDb->select($table, $conditions);
        
        $this->assertNotEmpty(
            $records,
            "Database should have record in '{$table}' matching conditions: " . json_encode($conditions)
        );
    }
    
    /**
     * Assert that a record does NOT exist in the database
     * 
     * @param string $table Database table name
     * @param array<string, mixed> $conditions Field => value conditions
     */
    protected function assertDatabaseMissingRecord(string $table, array $conditions): void
    {
        $records = $this->fakeDb->select($table, $conditions);
        
        $this->assertEmpty(
            $records,
            "Database should NOT have record in '{$table}' matching conditions: " . json_encode($conditions)
        );
    }
    
    /**
     * Assert that exactly N records exist matching conditions
     * 
     * @param string $table Database table name
     * @param array<string, mixed> $conditions Field => value conditions
     * @param int $expectedCount Expected number of records
     */
    protected function assertDatabaseCount(string $table, array $conditions, int $expectedCount): void
    {
        $records = $this->fakeDb->select($table, $conditions);
        
        $this->assertCount(
            $expectedCount,
            $records,
            "Database should have exactly {$expectedCount} record(s) in '{$table}' matching conditions"
        );
    }
    
    /**
     * Assert that response contains all expected text snippets
     * 
     * @param mixed $response Test response object
     * @param array<string> $expectedTexts Array of text snippets
     */
    protected function assertResponseContainsAll(mixed $response, array $expectedTexts): void
    {
        foreach ($expectedTexts as $text) {
            $response->assertSee($text);
        }
    }
    
    /**
     * Assert that response does NOT contain any of the given text snippets
     * 
     * @param mixed $response Test response object
     * @param array<string> $unexpectedTexts Array of text snippets
     */
    protected function assertResponseContainsNone(mixed $response, array $unexpectedTexts): void
    {
        foreach ($unexpectedTexts as $text) {
            $response->assertDontSee($text);
        }
    }
    
    /**
     * Assert that a form field has specific value in the response
     * 
     * @param mixed $response Test response object
     * @param string $fieldName Form field name
     * @param string $expectedValue Expected field value
     */
    protected function assertFormFieldValue(mixed $response, string $fieldName, string $expectedValue): void
    {
        $response->assertSee('name="' . $fieldName . '"');
        $response->assertSee('value="' . $expectedValue . '"');
    }
    
    /**
     * Assert that pagination is present in the response
     * 
     * @param mixed $response Test response object
     */
    protected function assertHasPagination(mixed $response): void
    {
        $response->assertSee('pagination');
    }
    
    /**
     * Assert that a specific record appears in the list
     * 
     * @param mixed $response Test response object
     * @param array<string> $recordIdentifiers Unique identifiers for the record
     */
    protected function assertRecordInList(mixed $response, array $recordIdentifiers): void
    {
        foreach ($recordIdentifiers as $identifier) {
            $response->assertSee($identifier);
        }
    }
}
