# InvoicePlane Development Guidelines

## Overview

This document establishes the security principles, code quality standards, and development best practices for the InvoicePlane project. These guidelines are derived from lessons learned through security audits, vulnerability remediation, and comprehensive test suite refactoring efforts.

**Purpose:** Ensure all InvoicePlane code adheres to industry-standard security practices, maintains high code quality, and follows consistent patterns across the codebase.

**Audience:** All developers, contributors, and code reviewers working on the InvoicePlane project.

## Table of Contents

1. [Security Principles](#security-principles)
2. [DRY Programming](#dry-programming)
3. [Input Validation and Sanitization](#input-validation-and-sanitization)
4. [Output Encoding](#output-encoding)
5. [File Security](#file-security)
6. [Logging Best Practices](#logging-best-practices)
7. [Testing Standards](#testing-standards)
8. [Code Review Checklist](#code-review-checklist)
9. [Frontend Development](#frontend-development)

---

## Security Principles

### Defense-in-Depth Strategy

InvoicePlane implements a **defense-in-depth** security architecture consisting of multiple, independent layers of protection. This approach ensures that if one security control fails, additional controls provide redundant protection.

#### Security Layers

1. **Input Sanitization** — Sanitize all user input at the controller level using framework security features
2. **Output Encoding** — Escape all data before rendering in views, regardless of sanitization status
3. **Validation** — Enforce format, type, and business rule constraints on all user-supplied data
4. **Access Control** — Verify user authentication and authorization at each application layer
5. **Secure Defaults** — Configure safe default values and implement secure failure modes

#### Implementation Example

The following example demonstrates the layered security approach in the `Admin_Controller`:
```php
// Layer 1: Global XSS sanitization for all POST fields
protected function filter_input(): void
{
    $input = $this->input->post();
    foreach ($input as $key => $value) {
        $cleaned_value = $this->security->xss_clean($value);
        $cleaned_value = strip_tags($cleaned_value);
        $_POST[$key] = $cleaned_value;
    }
}

// Layer 2: Additional validation in controllers
if (!preg_match('/^[A-Z0-9-]+$/', $invoice_number)) {
    // Reject invalid format
}

// Layer 3: Output encoding in views
<?php echo html_escape($invoice_number); ?>
```

### Addressed Vulnerability Categories

InvoicePlane development addresses the following OWASP-recognized vulnerability categories:

| Vulnerability Type | Mitigation Strategy |
|-------------------|---------------------|
| **XSS (Cross-Site Scripting)** | Input sanitization + context-aware output encoding |
| **LFI (Local File Inclusion)** | Path validation + template whitelisting |
| **Path Traversal** | Directory traversal detection + canonical path verification |
| **Log Injection** | Newline removal + sanitization before logging |
| **Header Injection** | Filename sanitization in HTTP headers |
| **SVG-based XSS** | Complete blocking of SVG file uploads |

**Note:** Each vulnerability type requires specific security controls as detailed in subsequent sections.

---

## DRY Programming

### Principle

**Don't Repeat Yourself (DRY):** Every piece of knowledge must have a single, unambiguous, authoritative representation within the system.

**Rationale:** Code duplication leads to inconsistent implementations, increased maintenance burden, and higher risk of bugs when changes are needed.

### Criteria for Extracting Helper Functions

Extract logic into a reusable helper function when any of the following conditions are met:

1. **Repetition Threshold** — The same logic appears in 3 or more locations across the codebase
2. **Complexity** — The logic is complex enough to benefit from isolated unit testing
3. **Security-Critical** — The logic addresses specific security concerns (e.g., sanitization, validation)
4. **Maintenance** — The logic is likely to change in the future, requiring centralized updates

### Refactoring Example: Log Sanitization

**Before (Code Duplication):**
```php
// In Settings.php
$safe_filename = preg_replace('/[[:^print:]]/', '', $_FILES['logo']['name']);
log_message('warning', 'Upload blocked: ' . $safe_filename);

// In pdf_helper.php
$safe_template = preg_replace('/[[:^print:]]/', '', $template_name);
log_message('error', 'Invalid template: ' . $safe_template);

// In Upload_Controller.php
$safe_name = str_replace(["\r", "\n"], '', $upload_name);
log_message('info', 'Processing: ' . $safe_name);
```

**After (DRY Implementation):**
```php
// Single source of truth in file_security_helper.php
function sanitize_for_logging(string $value): string
{
    // Removes newline characters to prevent log injection attacks
    return str_replace(["\r", "\n"], '', $value);
}

// Consistent usage across the application
log_message('warning', 'Upload blocked: ' . sanitize_for_logging(basename($_FILES['logo']['name'])));
log_message('error', 'Invalid template: ' . sanitize_for_logging($template_name));
log_message('info', 'Processing: ' . sanitize_for_logging($upload_name));
```

**Benefits of DRY Refactoring:**

- **Single Point of Maintenance** — Updates to sanitization logic only need to occur in one location
- **Consistent Security** — All log operations use the same sanitization approach
- **Testability** — Helper function can be unit tested in isolation
- **Clear Intent** — Function name documents its purpose and use case

### Helper Function Organization

Organize helper functions into domain-specific files based on their purpose:

| Helper File | Purpose |
|------------|---------|
| `file_security_helper.php` | File access validation, path security, logging sanitization |
| `pdf_helper.php` | PDF generation and manipulation utilities |
| `invoice_helper.php` | Invoice-specific business logic |
| `date_helper.php` | Date formatting and calculation operations |

---

## Input Validation and Sanitization

### Global Input Sanitization

InvoicePlane implements automatic sanitization for all POST data through the `Admin_Controller::filter_input()` method. This provides baseline protection across all 500+ POST fields in the application.

**Implementation:**

```php
protected function filter_input(): void
{
    foreach ($input as $key => $value) {
        // Apply XSS cleaning and strip dangerous HTML tags
        $cleaned_value = $this->security->xss_clean($value);
        $cleaned_value = strip_tags($cleaned_value);
        $_POST[$key] = $cleaned_value;
    }
}
```

**Coverage:** This global sanitization automatically protects against XSS attacks for all standard form submissions.

### Additional Validation Requirements

While global sanitization provides baseline protection, additional validation is required for:

1. **Format Enforcement** — Invoice numbers, tax codes, product SKUs requiring specific character sets
2. **Business Rule Validation** — Character limits, allowed character ranges, numeric constraints
3. **Type Safety** — Email addresses, numeric IDs, date formats, URLs

**Important:** Additional validation is for **format enforcement only**, not XSS protection (already handled globally).

### Sanitization Bypass Fields

Certain fields must bypass XSS sanitization to preserve legitimate special characters:

```php
$bypass_fields = [
    'user_password',        // Password fields require special character support
    'user_passwordv',       // Password verification field
    'invoice_password',     // PDF password protection feature
    'quote_password',       // PDF password protection feature
    'email_template_body',  // HTML email templates with legitimate markup
];
```

**Security Warning:** Bypass fields require mandatory output encoding and additional security review.

### Validation Pattern Examples

**Invoice Number Format:**
```php
if (!preg_match('/^[A-Z0-9-]+$/i', $invoice_number)) {
    $this->session->set_flashdata('alert_error', 'Invalid invoice number format');
    redirect('invoices/view/' . $invoice_id);
}
```

**Tax Rate Code:**
```php
if (!preg_match('/^[A-Z0-9_-]+$/i', $tax_rate_code)) {
    // Reject input containing invalid characters
}
```

**Email Address:**
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Email format validation failed
}
```

---

## Output Encoding

### Mandatory Output Encoding

**Critical Security Rule:** Never rely solely on input sanitization. Always encode output data based on the context in which it will be rendered.

**Rationale:** Input sanitization may be bypassed, incomplete, or disabled for legitimate use cases. Output encoding provides a final layer of XSS protection.

### Context-Specific Encoding Strategies

Different rendering contexts require different encoding approaches:

#### HTML Context
```php
<!-- Standard HTML content -->
<h1><?php echo html_escape($invoice_number); ?></h1>
<div><?php echo html_escape($client_name); ?></div>
<textarea><?php echo html_escape($notes); ?></textarea>
```

#### JavaScript Context
```php
<script>
// JSON encoding with additional flag protection
var data = <?php echo json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>
```

#### URL Context
```php
<?php
// Use framework URL helpers and encode parameters
$base_url = site_url('invoices/view'); // Framework-validated base URL
$query_param = urlencode($invoice_id);    // URL-encode user data
?>
<a href="<?php echo $base_url . '/' . $query_param; ?>">View Invoice</a>
```

#### HTML Attribute Context
```php
<!-- Encode data even within HTML attributes -->
<input type="text" value="<?php echo html_escape($value); ?>">
<div data-id="<?php echo html_escape($record_id); ?>">Content</div>
```

**Encoding Function Reference:**

| Context | Function | Purpose |
|---------|----------|---------|
| HTML | `html_escape()` | Converts special characters to HTML entities |
| JavaScript | `json_encode()` | Encodes data as JSON with security flags |
| URL | `urlencode()` | Percent-encodes special characters for URLs |
| Attributes | `html_escape()` | Escapes quotes and special characters |

---

## File Security

### Path Traversal Prevention

**Critical:** Always validate file paths to prevent directory traversal attacks (e.g., `../../../etc/passwd`).

**Implementation Using Security Helpers:**

```php
// Step 1: Validate filename safety
$validation = validate_safe_filename($filename);
if (!$validation['valid']) {
    log_message('error', 'Invalid filename (hash: ' . $validation['hash'] . ')');
    show_error('Invalid filename');
}

// Step 2: Construct and validate resolved path
$fullPath = $baseDirectory . '/' . basename($filename);
if (!validate_file_in_directory($fullPath, $baseDirectory)) {
    log_message('error', 'Path traversal attempt detected');
    show_error('Access denied');
}
```

**Security Checks Performed:**
1. Filename contains no path traversal sequences (`../`, `..\\`)
2. Resolved canonical path remains within allowed directory
3. Symbolic links do not point outside allowed boundaries

### File Upload Security Requirements

All file upload functionality must implement the following controls:

1. **Extension Whitelist** — Validate file extensions against an allowed list
2. **Type Blocking** — Explicitly block dangerous file types (SVG, PHP, executables)
3. **Filename Sanitization** — Remove special characters and normalize filenames
4. **Directory Permissions** — Store uploads with restrictive permissions (0644 for files, 0755 for directories)
5. **Audit Logging** — Log all upload attempts with hashed filenames for security review

**File Upload Implementation Example:**

```php
// Validate file extension against whitelist
$extension = strtolower(pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION));
$allowed = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];

if (!in_array($extension, $allowed, true)) {
    log_message('warning', 'Blocked upload: ' . sanitize_for_logging(basename($_FILES['upload']['name'])));
    show_error('File type not allowed');
}

// Additional validation: Check MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['upload']['tmp_name']);
finfo_close($finfo);

$allowed_mimes = ['image/png', 'image/jpeg', 'image/gif', 'application/pdf'];
if (!in_array($mime, $allowed_mimes, true)) {
    log_message('warning', 'MIME type mismatch for upload');
    show_error('Invalid file type');
}
```

### Template Validation (LFI Prevention)

Prevent Local File Inclusion attacks by validating template names against a whitelist:

```php
/**
 * Validates template name to prevent LFI attacks
 *
 * @param string|null $template Template name to validate
 * @param string $type Template type (invoice, quote, etc.)
 * @param string $format Output format (pdf, html, etc.)
 * @return string|false Validated template name or false on failure
 */
function validate_template_name(?string $template, string $type, string $format): string|false
{
    // Whitelist of allowed templates
    $allowed_templates = ['InvoicePlane', 'Modern', 'Classic'];
    
    if ($template === null || !in_array($template, $allowed_templates, true)) {
        return false;
    }
    
    // Verify template file exists in expected location
    $template_path = APPPATH . "views/{$type}_templates/{$format}/{$template}.php";
    if (!file_exists($template_path)) {
        return false;
    }
    
    return $template;
}
```

**Security Benefits:**
- Prevents arbitrary file inclusion through template parameter manipulation
- Validates template exists before inclusion
- Uses strict whitelist approach (deny by default)

---

## Logging Best Practices

### Log Injection Prevention

**Critical Security Rule:** Never log untrusted user data directly. Log injection attacks can create false log entries or manipulate log analysis tools.

**Vulnerability Example:**

```php
// ❌ VULNERABLE - Allows log injection
log_message('error', 'Failed login: ' . $_POST['username']);

// Attacker input: "admin\nSUCCESS: Admin logged in"
// Creates false successful login entry in logs
```

**Secure Implementation:**

```php
// ✅ CORRECT - Sanitizes newlines before logging
log_message('error', 'Failed login: ' . sanitize_for_logging($_POST['username']));
```

### Protecting Sensitive Data in Logs

For filenames, paths, and other sensitive data, log **cryptographic hashes** instead of raw values:

**Implementation:**

```php
// Log the hash rather than the actual filename
$hash = hash('sha256', $filename);
log_message('error', 'Invalid file access (hash: ' . $hash . ')');
```

**Benefits:**
- Prevents information disclosure through log files
- Allows correlation of events using hash values
- Protects user privacy and sensitive file information

### Structured Logging for Complex Events

For security events requiring multiple data points, use structured JSON logging:

```php
$log_context = [
    'timestamp' => date('Y-m-d H:i:s'),
    'user_id' => $this->session->userdata('user_id'),
    'uri' => uri_string(),
    'ip_address' => $this->input->ip_address(),
    'event_type' => 'xss_attempt',
    'fields' => $xss_log_entries,  // Array of affected fields
];

$log_payload = json_encode($log_context, JSON_PARTIAL_OUTPUT_ON_ERROR);
log_message('error', 'XSS attempt detected: ' . $log_payload);
```

**Advantages of Structured Logging:**
- Easier parsing and analysis by log management tools
- Consistent format across all security events
- Enables automated alerting and monitoring
- Graceful handling of encoding errors with `JSON_PARTIAL_OUTPUT_ON_ERROR`

---

## Testing Standards

### Critical Rule: Complete Test Data Required

**NEVER write fragile tests with minimal or incomplete data.** Fragile tests create false confidence and waste significant debugging time when they fail for the wrong reasons.

#### Prohibited: Minimal Test Data

❌ **FORBIDDEN - Missing required fields:**

```php
// Missing critical fields like client_id, note content, timestamps
$this->fakeDb->insert('ip_client_notes', ['client_note_id' => 1]);

// Partial form submission doesn't match real-world usage
$this->post('/users/form', ['user_name' => 'Test']);
```

#### Required: Complete Test Data

✅ **REQUIRED - Complete, realistic data:**

```php
// Use complete fixture data with all required fields
$noteData = [
    'client_note_id' => 1,
    'client_id' => $activeClient['client_id'],
    'client_note' => 'Complete test note content',
    'client_note_date' => date('Y-m-d H:i:s'),
];
$this->fakeDb->insert('ip_client_notes', $noteData);

// Use trait data builders that provide complete forms
$completeData = $this->makeUserData(['btn_submit' => '1']);
$response = $this->post('/users/form', $completeData);
```

### Critical Rule: Explicit Assertions Required

**NEVER use trait assertion methods that hide implementation details.** Every assertion must be explicit and immediately visible in the test code.

#### Prohibited: Hidden Trait Assertions

❌ **FORBIDDEN - Cannot verify what this checks without examining trait:**

```php
$this->assertJsonResponseSuccess($response);
$this->assertNotFoundResponse($response);
$this->assertDatabaseHasRecord('users', ['email' => 'test@example.com']);
```

#### Required: Explicit Assertions

✅ **REQUIRED - Clear, explicit status and content checks:**

```php
// Explicit HTTP status assertions
$response->assertStatus(200);
$response->assertStatus(404);
$response->assertStatus(403);

// Explicit JSON content and structure validation
$response->assertJson(['success' => true]);
$response->assertJsonStructure(['data', 'message']);

// Explicit database assertions
$records = $this->fakeDb->select('ip_users', ['user_email' => 'test@example.com']);
$this->assertNotEmpty($records, "Database should contain user record");
$this->assertCount(1, $records, "Should have exactly one matching record");
```

### Security Function Testing

All security-critical functions must have comprehensive unit tests covering both valid and attack scenarios:

**Log Injection Prevention Test:**

```php
#[Test]
public function it_sanitizes_log_injection_attempts(): void
{
    /* Arrange */
    $malicious = "test\nFAKE LOG ENTRY";
    
    /* Act */
    $result = sanitize_for_logging($malicious);
    
    /* Assert */
    $this->assertEquals('testFAKE LOG ENTRY', $result);
    $this->assertStringNotContainsString("\n", $result);
    $this->assertStringNotContainsString("\r", $result);
}
```

**Path Traversal Prevention Test:**

```php
#[Test]
public function it_blocks_path_traversal_attempts(): void
{
    /* Arrange */
    $malicious_path = '../../../etc/passwd';
    
    /* Act */
    $validation = validate_safe_filename($malicious_path);
    
    /* Assert */
    $this->assertFalse($validation['valid']);
    $this->assertEquals('path_traversal', $validation['error']);
}
```

### Test Naming Convention

Follow consistent naming patterns for all test methods:

- **Prefix:** Use `it_` for all test method names
- **Case:** Use `snake_case` for test method names (not camelCase)
- **Readability:** Test names should read as complete sentences describing behavior
- **Annotation:** Use `#[Test]` attribute instead of `test` prefix

**Example:**

```php
#[Test]
public function it_validates_invoice_number_format(): void
{
    /* Arrange */
    $this->actAsAdmin();
    $invalidInvoiceNumber = 'INV#@$%123';
    
    /* Act */
    $result = $this->validateInvoiceNumber($invalidInvoiceNumber);
    
    /* Assert */
    $this->assertFalse($result['valid']);
    $this->assertEquals('invalid_format', $result['error']);
}
```

### Test Structure: Arrange-Act-Assert

All tests must follow the Arrange-Act-Assert pattern with explicit comment markers:

```php
#[Test]
public function it_creates_new_invoice_with_valid_data(): void
{
    /* Arrange */
    $this->actAsAdmin();
    $invoiceData = $this->makeInvoiceData(['client_id' => 1]);
    
    /* Act */
    $response = $this->post('/invoices/form', $invoiceData);
    
    /* Assert */
    $response->assertStatus(302);
    $records = $this->fakeDb->select('ip_invoices', ['invoice_number' => $invoiceData['invoice_number']]);
    $this->assertNotEmpty($records, "Invoice should be created in database");
}
```

### Comprehensive Test Refactoring: One-Prompt Solution

**Problem:** Test refactoring should not require 20+ iterative prompts to achieve quality standards.

**Root Cause:** Previous instructions lacked explicit requirements for what constitutes "meaningful," "sensible," and "non-lazy" tests.

#### The Perfect Single Prompt

When requesting comprehensive test refactoring, use this exact prompt structure:

```
Refactor ALL test files in modules/ to production-ready quality standards. 
For EVERY test in EVERY file:

1. MEANINGFUL ARRANGE - Complete, realistic test data:
   - Use 15-20 fields per database record (all required fields)
   - Create complete fixture data matching real-world scenarios
   - NO minimal data (e.g., only ID fields)
   - NO partial form submissions

2. AMAZING ACT - Clear documentation:
   - Add PHPDoc block above each request documenting:
     * HTTP method and endpoint
     * Complete POST/GET data structure
     * Expected response structure (JSON/HTML/PDF)
   - Example:
     /**
      * Act: POST /clients/ajax/delete_note
      * POST data: {"note_id": "123"}
      * Expected JSON: {"success": true, "message": "Note deleted"}
      */

3. MEANINGFUL ASSERTIONS - Minimum 3 categories per test:
   - Data verification: Specific values from fixtures (invoice numbers, amounts, names)
   - Structure verification: JSON keys, HTML table headers, response format
   - Database verification: Record existence, CRUD operations, state changes
   - NO abstract helpers (assertResponseSuccess, assertJsonResponse)
   - NO empty assertions (assertJson([]))
   - NO status-only checks without content verification

Quality Gates:
- Zero test deletions (preserve ALL existing tests)
- Average 6+ assertions per test
- Zero trait assertion abstractions
- Every assertion explicitly visible inline
- Complete fixture data for all Arrange phases

Work systematically through all 52 test files. Report progress after each file.
Estimated effort: 130 hours across 900+ test methods.
```

#### Why This Works

This comprehensive prompt works because it:

1. **Eliminates ambiguity** — "Meaningful" and "non-lazy" are explicitly defined with examples
2. **Provides measurable criteria** — "6+ assertions," "15-20 fields," "3 categories"
3. **Shows anti-patterns** — Lists what NOT to do alongside correct patterns
4. **Sets quality gates** — Specific metrics for validating completion
5. **Manages expectations** — States effort level (130 hours) upfront

#### Application to This Repository

Following this prompt pattern, an agent would immediately understand:

- **Arrange:** Every test needs complete client data with all fields:
  ```php
  $clientData = [
      'client_id' => 1,
      'client_name' => 'ACME Corp',
      'client_email' => 'contact@acme.com',
      'client_phone' => '555-0100',
      'client_address_1' => '123 Main St',
      'client_city' => 'Springfield',
      'client_state' => 'IL',
      'client_zip' => '62701',
      'client_country' => 'USA',
      'client_active' => 1,
      'client_url_key' => md5('acme' . time()),
      'client_date_created' => date('Y-m-d H:i:s'),
      'client_date_modified' => date('Y-m-d H:i:s'),
  ];
  ```

- **Act:** PHPDoc blocks with complete request/response documentation
- **Assert:** Minimum 3 explicit verification categories (data + structure + database)

#### Lessons Learned

**What went wrong in 20+ prompts:**
1. Terms like "sturdy" and "lazy" were subjective without concrete definitions
2. No quantitative metrics (e.g., "3+ assertions," "15+ fields")
3. Missing explicit anti-patterns showing what NOT to do
4. No quality gates to verify completion

**What the ideal prompt provides:**
1. Concrete examples of complete test data structures
2. Measurable quality metrics (assertion count, field count)
3. Explicit list of forbidden patterns alongside required patterns
4. Clear success criteria for each test

---

## Code Review Checklist

### Security Review Checklist

Before approving any code changes, verify the following security controls:

- [ ] **Input Sanitization** — All user input is sanitized (document any bypass with justification)
- [ ] **Output Encoding** — All output is encoded with context-appropriate functions
- [ ] **Path Validation** — File paths are validated to prevent directory traversal
- [ ] **Log Sanitization** — Log messages remove newlines and sensitive data
- [ ] **SQL Safety** — Database queries use parameterized statements or Query Builder
- [ ] **File Upload Controls** — File uploads validate extensions, types, and sizes
- [ ] **Header Safety** — HTTP headers sanitize user-supplied data
- [ ] **Authentication** — Protected endpoints verify user authentication
- [ ] **Authorization** — Operations verify user permissions and ownership

### Code Quality Review Checklist

Evaluate code quality and maintainability:

- [ ] **No Duplication** — DRY principle applied; common logic extracted to helpers
- [ ] **Helper Usage** — Common operations use established helper functions
- [ ] **Single Responsibility** — Functions and classes have single, clear purposes
- [ ] **Code Documentation** — Complex logic includes explanatory comments
- [ ] **Error Handling** — Consistent error handling with appropriate logging
- [ ] **Test Coverage** — Critical paths have comprehensive test coverage
- [ ] **Documentation** — README and inline documentation reflects changes

### Framework Compliance Checklist

Ensure adherence to framework standards and best practices:

- [ ] **Security Features** — Uses framework functions (`xss_clean`, `html_escape`, `Query Builder`)
- [ ] **Coding Standards** — Follows PSR-12 coding style guidelines
- [ ] **Type Safety** — Uses PHP type hints for parameters and return values where appropriate
- [ ] **No Deprecated APIs** — Avoids deprecated framework functions and patterns
- [ ] **Configuration** — Uses environment variables for configuration, not hardcoded values
- [ ] **Performance** — No obvious performance anti-patterns (N+1 queries, unnecessary loops)

---

## Frontend Development

### Build Environment and CLI Commands

**Important:** This repository supports standard Node.js and PHP CLI commands for development workflows.

**Available Commands:**
- `npm run build` — Production build of frontend assets via Vite
- `npm run dev` — Development server with hot module replacement
- `php -l <file>` — PHP syntax validation
- `vendor/bin/phpunit` — PHPUnit test execution (when available)

**Usage:** CLI command output provides immediate feedback for verifying changes. Do not assume commands are unavailable without testing.

### Asset Compilation with Sass and Vite

The frontend build system currently uses both Sass and Vite for asset compilation:

**Source Structure:**
- **Sass Files:** `resources/assets/**/{sass,scss}/*.scss`
- **JavaScript:** `resources/assets/js/**/*.js`

**Build Output:**
- **Destination:** `public/assets/`
- **Format:** Compiled CSS and JavaScript bundles optimized for production

**Workflow:**
1. Edit source files in `resources/assets/`
2. Run `npm run build` to compile changes
3. Verify output in `public/assets/`
4. Test functionality in browser

### JavaScript Refactoring Standard

When migrating inline JavaScript from PHP templates to module-based JavaScript:

#### Goals

1. **Separation of Concerns** — Move JavaScript behavior from PHP views to dedicated module files
2. **Maintainability** — Centralize JavaScript logic for easier testing and updates
3. **Backward Compatibility** — Preserve existing functionality and route behavior

#### Implementation Pattern

**Before (Inline JavaScript in PHP):**

```php
<script>
$(document).ready(function() {
    $('#save-button').click(function() {
        $.post('/invoices/save', {
            invoice_id: <?php echo $invoice_id; ?>,
            csrf_token: '<?php echo $csrf_token; ?>'
        });
    });
});
</script>
```

**After (Modular JavaScript):**

```php
<!-- In PHP template: Data attributes only -->
<button 
    id="save-button"
    data-route="<?php echo site_url('invoices/save'); ?>"
    data-invoice-id="<?php echo html_escape($invoice_id); ?>"
    data-csrf-name="<?php echo $this->security->get_csrf_token_name(); ?>"
    data-csrf-value="<?php echo $this->security->get_csrf_hash(); ?>">
    Save
</button>
```

```javascript
// In resources/assets/js/modules/invoice-manager.js
export class InvoiceManager {
    constructor() {
        this.bindEvents();
    }
    
    bindEvents() {
        $('#save-button').on('click', (e) => this.handleSave(e));
    }
    
    handleSave(event) {
        const button = $(event.currentTarget);
        const route = button.data('route');
        const invoiceId = button.data('invoice-id');
        const csrfName = button.data('csrf-name');
        const csrfValue = button.data('csrf-value');
        
        const postData = {
            invoice_id: invoiceId,
            [csrfName]: csrfValue
        };
        
        $.post(route, postData)
            .done((response) => this.handleSuccess(response))
            .fail((error) => this.handleError(error));
    }
}
```

#### Data Passing Guidelines

**Use `data-*` attributes for:**
- Server-generated routes and URLs
- Record IDs and identifiers
- CSRF token names and values
- Boolean flags and configuration options

**Avoid passing in `data-*` attributes:**
- Large objects or arrays (use AJAX endpoints instead)
- Sensitive information that shouldn't be in HTML
- Complex nested data structures

#### Best Practices

1. **Preserve jQuery Usage** — Existing jQuery code should remain intact for compatibility
2. **Maintain Route Behavior** — Keep existing POST/GET behavior and endpoints unchanged
3. **Test Thoroughly** — Verify all interactive features work after refactoring
4. **Document Changes** — Comment modules explaining their purpose and usage

---

## Document Summary

This document establishes comprehensive guidelines for:

1. **Security** — Multi-layered defense against common web vulnerabilities
2. **Code Quality** — DRY principles and maintainable code organization
3. **Testing** — Complete data and explicit assertions for reliable tests
4. **Frontend** — Modern JavaScript patterns while preserving functionality

### Key Principles

- **Security First** — Every feature implements multiple layers of protection
- **Maintainability** — DRY principle reduces duplication and centralizes changes
- **Reliability** — Consistent patterns and comprehensive testing reduce bugs
- **Clarity** — Explicit code and documentation make intent clear

### When in Doubt

Ask these three questions before committing code:

1. **Is this secure?** Does it implement appropriate security controls?
2. **Is this DRY?** Have I eliminated unnecessary duplication?
3. **Is this clear?** Will other developers understand this code in six months?

---

**Document Version:** 2.0  
**Last Updated:** 2026-03-29  
**Maintained By:** InvoicePlane Development Team
