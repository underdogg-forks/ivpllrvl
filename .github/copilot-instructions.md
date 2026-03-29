# GitHub Copilot Instructions for InvoicePlane

This document provides specific guidance for GitHub Copilot when working with the InvoicePlane codebase.

## Project Overview

InvoicePlane is a libre self-hosted invoicing application built with:
- **Backend:** PHP 8.1+ with CodeIgniter 3 framework (legacy v1)
- **Frontend:** JavaScript, jQuery, HTML, CSS
- **Database:** MySQL/MariaDB
- **Build Tools:** Vite, Sass, Tailwind CSS
- **Testing:** PHPUnit for PHP tests

## Repository Structure

```
/
├── .github/                 # GitHub-specific files (workflows, instructions)
├── application/             # CodeIgniter 3 application files
│   ├── controllers/         # Legacy controllers (being migrated to modules)
│   ├── models/              # Legacy models
│   ├── views/               # View templates
│   └── helpers/             # Helper functions (including security helpers)
├── modules/                 # Modular application structure
│   ├── core/                # Core module (base classes, utilities)
│   ├── invoices/            # Invoice management
│   ├── quotes/              # Quote management
│   ├── clients/             # Client management
│   ├── products/            # Product/services management
│   ├── projects/            # Project tracking
│   └── payments/            # Payment processing
├── resources/               # Frontend assets (source)
│   └── assets/
│       ├── js/              # JavaScript source files
│       ├── sass/            # Sass stylesheets
│       └── scss/            # SCSS stylesheets
├── public/                  # Web-accessible directory
│   ├── assets/              # Compiled frontend assets (Vite output)
│   └── index.php            # Application entry point
├── config/                  # Configuration files
├── uploads/                 # User-uploaded files
├── vendor/                  # PHP dependencies (Composer)
├── node_modules/            # JavaScript dependencies (Yarn)
├── composer.json            # PHP dependency definitions
├── package.json             # JavaScript dependency definitions
├── phpunit.xml              # PHPUnit test configuration
├── vite.config.js           # Vite build configuration
└── phpcs.xml                # PHP CodeSniffer configuration
```

### Key File Locations

- **Security helpers:** `application/helpers/file_security_helper.php`
- **PDF generation:** `application/helpers/pdf_helper.php`
- **Main controller:** `application/core/Admin_Controller.php`
- **Base test class:** `modules/core/Testing/TestCase.php`
- **Vite config:** `vite.config.js` (frontend build configuration)
- **CI/CD workflows:** `.github/workflows/`

## Environment Setup

### Local Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/InvoicePlane/InvoicePlane.git
   cd InvoicePlane
   ```

2. **Setup Docker environment (recommended):**
   ```bash
   cp .env.example .env
   docker-compose up --build -d
   ```

3. **Install dependencies:**
   ```bash
   composer install
   yarn install
   ```

4. **Build frontend assets:**
   ```bash
   npm run build
   ```

5. **Configure application:**
   ```bash
   cp ipconfig.php.example ipconfig.php
   # Edit ipconfig.php to set your base URL
   ```

### System Requirements

- **PHP:** 8.1 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Node.js:** Version specified in `.node-version` file
- **Web Server:** Apache with mod_rewrite or Nginx

### Important Notes

- **Always run `yarn install` before building** - Missing dependencies (like `glob`) will cause build failures
- **Docker is the recommended development environment** - See `docker-compose.yml` for configuration
- **Frontend changes require rebuild** - Run `npm run build` after modifying JavaScript or Sass files
- **PHP extensions required:** mbstring, openssl, pdo_mysql, gd, curl, zip

## Code Style and Conventions

### PHP Code Standards

- Follow **PSR-12** coding standards
- Use **type hints** for all parameters and return types where possible
- Use **strict comparison** (`===`, `!==`) instead of loose comparison
- All test methods start with `it_`, use snake_case, and are annotated with `#[Test]`
- Tests follow the **Arrange, Act, Assert** pattern

Example:
```php
#[Test]
public function it_validates_safe_filename(): void
{
    // Arrange
    $filename = '../../../etc/passwd';
    
    // Act
    $result = validate_safe_filename($filename);
    
    // Assert
    $this->assertFalse($result['valid']);
    $this->assertEquals('path_traversal', $result['error']);
}
```

### Framework-Specific Patterns

#### CodeIgniter 3 (Current)

InvoicePlane v1.x uses CodeIgniter 3:

```php
// Controller structure
class Invoice extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mdl_invoices');
        $this->load->helper('invoice');
    }
}

// Database queries - Use Query Builder
$this->db->where('invoice_id', $invoice_id);
$query = $this->db->get('ip_invoices');

// Security features
$cleaned = $this->security->xss_clean($input);
$escaped = html_escape($output);
```

#### Laravel (Future)

InvoicePlane is migrating to Laravel. When working on Laravel code:

```php
// Use Laravel Filament as much as possible
// Use Eloquent ORM
// Follow Laravel conventions
```

## Security-First Development

### Input Sanitization Strategy

InvoicePlane uses a **defense-in-depth** approach:

1. **Global XSS sanitization** happens in `Admin_Controller::filter_input()` for ALL POST fields
2. **Individual regex patterns** are ONLY for format validation, not XSS protection
3. **Output encoding** is always required in views

```php
// CORRECT: Trust global sanitization, add format validation
protected function filter_input(): void
{
    // Global sanitization handles XSS for all 500+ POST fields
    $cleaned_value = $this->security->xss_clean($value);
    $cleaned_value = strip_tags($cleaned_value);
    $_POST[$key] = $cleaned_value;
}

// THEN in controller: Add format validation ONLY
if (!preg_match('/^[A-Z0-9-]+$/i', $invoice_number)) {
    // Reject invalid FORMAT, not for XSS protection
}
```

### Never Skip These Security Checks

1. **Output encoding** - Always use `html_escape()` in views
2. **Path validation** - Always validate file paths with helpers from `file_security_helper.php`
3. **Log sanitization** - Always use `sanitize_for_logging()` before logging user input
4. **SQL parameterization** - Always use Query Builder or prepared statements
5. **File upload validation** - Always validate extension, block SVG files

### Security Helper Functions

Use these helper functions from `file_security_helper.php`:

```php
// Validate filename safety
$validation = validate_safe_filename($filename);
if (!$validation['valid']) {
    log_message('error', 'Invalid filename (hash: ' . $validation['hash'] . ')');
    show_error('Invalid filename');
}

// Validate file is in allowed directory
if (!validate_file_in_directory($fullPath, $baseDirectory)) {
    show_error('Access denied');
}

// Sanitize for logging (prevents log injection)
log_message('error', 'Upload failed: ' . sanitize_for_logging($filename));

// Sanitize filename for HTTP headers (prevents header injection)
$safe = sanitize_filename_for_header($filename);
header('Content-Disposition: attachment; filename="' . $safe . '"');
```

## DRY Programming Guidelines

### When to Extract a Helper Function

Extract logic into a helper function when:

1. The same logic appears **3+ times** across the codebase
2. The logic addresses a **security concern** (e.g., sanitization, validation)
3. The logic is **complex** and would benefit from isolated testing
4. The logic might **need to change** in the future

### Example: Before and After

**Before (Code Duplication):**
```php
// In multiple files, different patterns for the same goal
$safe1 = preg_replace('/[[:^print:]]/', '', $value);
$safe2 = str_replace(["\r", "\n"], '', $value);
$safe3 = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
```

**After (DRY with Helper):**
```php
// Single helper function
function sanitize_for_logging(string $value): string
{
    return str_replace(["\r", "\n"], '', $value);
}

// Used everywhere consistently
log_message('error', 'Error: ' . sanitize_for_logging($value));
```

### Helper Function Organization

Place functions in appropriate helper files:

- `file_security_helper.php` - File operations, path validation, log sanitization
- `pdf_helper.php` - PDF generation utilities
- `invoice_helper.php` - Invoice-specific business logic
- `date_helper.php` - Date formatting and manipulation

## Build and Development Commands

### Prerequisites

Before building or testing:

1. **Install Node.js dependencies:**
   ```bash
   yarn install
   ```
   Note: The project uses Yarn for JavaScript package management.

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

### Frontend Build

The project uses **Vite** for frontend asset bundling:

```bash
# Development mode with hot reload
npm run dev

# Production build
npm run build
```

**Important:** 
- Always run `npm run build` after making frontend changes
- Vite outputs compiled assets to `public/assets`
- The build includes Sass compilation from `resources/assets/**/{sass,scss}/*.scss`
- Build time: ~3-5 seconds

### Code Quality Tools

```bash
# Run all checks (rector, phpcs, pint)
composer check

# Run PHP linter (Rector)
composer rector

# Run PHP Code Sniffer
composer phpcs

# Run Laravel Pint
composer pint

# Prettier for frontend code
npm run prettier
npm run prettier:check
```

### Common Build Issues

**Issue:** `Error: Cannot find module 'glob'`
**Solution:** The glob package is required but may not be in package.json. Run `yarn add glob --dev`.

**Issue:** Build fails with sass errors
**Solution:** Ensure `sass` is installed: `yarn add sass --dev`

## Testing Requirements

### CRITICAL: NEVER Write Fragile Tests

Tests must use **complete, realistic data**. Fragile tests waste time and create false confidence.

**❌ FORBIDDEN - Minimal/Incomplete Test Data:**
```php
// Missing required fields - test will fail for wrong reasons
$this->fakeDb->insert('ip_client_notes', ['client_note_id' => 1]);

// Partial form data - doesn't match real usage
$this->post('/users/form', ['user_name' => 'Test']);
```

**✅ REQUIRED - Complete Test Data:**
```php
// Use complete fixture data
$noteData = [
    'client_note_id' => 1,
    'client_id' => $activeClient['client_id'],
    'client_note' => 'Test note content',
    'client_note_date' => date('Y-m-d H:i:s'),
];
$this->fakeDb->insert('ip_client_notes', $noteData);

// Use trait data builders for complete forms
$completeData = $this->makeUserData(['btn_submit' => '1']);
$response = $this->post('/users/form', $completeData);
```

### CRITICAL: Use Explicit Assertions Only

**❌ FORBIDDEN - Trait assertion abstractions:**
```php
// Can't verify what this actually checks without digging into trait
$this->assertJsonResponseSuccess($response);
$this->assertNotFoundResponse($response);
```

**✅ REQUIRED - Explicit, visible assertions:**
```php
// Clear, explicit checks visible in the test
$response->assertStatus(200);
$response->assertStatus(404);
$response->assertStatus(403);
$response->assertJson(['success' => true]);
```

### Test Structure

All tests must follow this structure:

```php
<?php

namespace Modules\Projects\Tests;

use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProjectsControllerTest extends TestCase
{
    #[Test]
    public function it_validates_project_name(): void
    {
        // Arrange
        $project_name = '../../../etc/passwd';
        
        // Act
        $result = validate_project_name($project_name);
        
        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('invalid_format', $result['error']);
    }
}
```

### Test Coverage Requirements

- **All security functions** must have tests
- **All helper functions** must have tests
- **Critical business logic** must have tests
- **Edge cases** must be covered (empty strings, null, special characters)

### Running Tests

Tests are organized in module-specific directories:

```bash
# Tests are located in modules/*/tests/
# Example test locations:
# - modules/projects/tests/ProjectsControllerTest.php
# - modules/products/tests/ProductsControllerTest.php
# - modules/invoices/tests/InvoicesControllerTest.php

# Note: PHPUnit configuration is in phpunit.xml
# Test namespace: Use Modules\ModuleName\Tests
```

## Comprehensive Test Refactoring - One-Prompt Solution

**CRITICAL:** When asked to "refactor tests," "improve test quality," or make tests "meaningful/sensible/non-lazy," use this comprehensive approach in a SINGLE prompt execution.

### The Perfect Refactoring Prompt Pattern

When you receive a request like "refactor all my tests to being meaningful and sensible and non-lazy," immediately apply ALL of the following standards to EVERY test in EVERY file:

#### 1. MEANINGFUL ARRANGE (Complete Realistic Data)

**Requirement:** Every test must use complete, realistic fixture data with 15-20 fields per record.

**❌ NEVER:**
```php
// Minimal data - missing required fields
$this->fakeDb->insert('ip_client_notes', ['client_note_id' => 1]);

// Partial form - doesn't match real usage
$this->post('/users/form', ['user_name' => 'Test']);
```

**✅ ALWAYS:**
```php
// Complete client data with all required fields
$clientData = [
    'client_id' => 1,
    'client_name' => 'ACME Corporation',
    'client_surname' => 'Smith',
    'client_email' => 'contact@acme.com',
    'client_phone' => '555-0100',
    'client_address_1' => '123 Main Street',
    'client_city' => 'Springfield',
    'client_state' => 'IL',
    'client_zip' => '62701',
    'client_country' => 'USA',
    'client_active' => 1,
    'client_url_key' => md5('acme' . time()),
    'client_date_created' => date('Y-m-d H:i:s'),
    'client_date_modified' => date('Y-m-d H:i:s'),
];
$this->fakeDb->insert('ip_clients', $clientData);

// Complete form submission using trait data builders
$userData = $this->makeUserData(['btn_submit' => '1']);
$response = $this->post('/users/form', $userData);
```

#### 2. AMAZING ACT (Clear Documentation)

**Requirement:** Every HTTP request must have a PHPDoc block documenting the complete request/response structure.

**❌ NEVER:**
```php
// No documentation
$response = $this->post('/ajax/delete', ['id' => 1]);
```

**✅ ALWAYS:**
```php
/**
 * Act: POST /clients/clientsajax/delete_note
 * POST data: {
 *   "note_id": "123"
 * }
 * Expected JSON response: {
 *   "success": true,
 *   "message": "Client note deleted successfully"
 * }
 */
$response = $this->post('/clients/clientsajax/delete_note', [
    'note_id' => $noteData['client_note_id'],
]);
```

#### 3. MEANINGFUL ASSERTIONS (3+ Categories)

**Requirement:** EVERY test must have minimum 3 assertion categories. NO exceptions.

**The Three Required Categories:**

1. **Data Assertions** — Verify specific values from fixtures
2. **Structure Assertions** — Verify response format, headers, keys
3. **Database Assertions** — Verify CRUD operations and state changes

**❌ NEVER:**
```php
// Lazy: Only status check
$response->assertOk();

// Lazy: Empty JSON assertion verifies nothing
$response->assertJson([]);

// Lazy: Trait abstraction hides implementation
$this->assertResponseSuccess($response);
```

**✅ ALWAYS:**
```php
/* Assert - Response Status & Headers */
$response->assertStatus(200);
$response->assertHeader('Content-Type', 'application/json; charset=utf-8');

/* Assert - JSON Structure & Data */
$jsonData = json_decode($response->getContent(), true);
$this->assertIsArray($jsonData);
$this->assertArrayHasKey('success', $jsonData);
$this->assertTrue($jsonData['success'], 'Response should indicate success');
$this->assertArrayHasKey('data', $jsonData);
$this->assertEquals('ACME Corporation', $jsonData['data']['client_name']);
$this->assertEquals('contact@acme.com', $jsonData['data']['client_email']);

/* Assert - Database State */
$clients = $this->fakeDb->select('ip_clients', ['client_id' => 1]);
$this->assertNotEmpty($clients, "Client record should exist in database");
$this->assertCount(1, $clients, "Should have exactly one client record");
$this->assertEquals('ACME Corporation', $clients[0]['client_name']);
```

### Quality Metrics for Test Refactoring

Use these measurable criteria to validate completion:

| Metric | Target | How to Verify |
|--------|--------|---------------|
| **Assertions per test** | 6+ average | Count assertion calls in each test method |
| **Fields per record** | 15-20 minimum | Count array keys in fakeDb inserts |
| **Trait abstractions** | 0 (zero) | Search for `$this->assert[A-Z].*Response\|Database` |
| **Empty assertions** | 0 (zero) | Search for `assertJson\(\[\]\)` |
| **PHPDoc blocks** | 100% coverage | Every HTTP request has documentation |
| **Test deletions** | 0 (zero) | Compare test count before/after |

### Execution Strategy

When refactoring 900+ tests across 50+ files:

1. **Audit First** — Identify all weak tests (0 assertions, <3 assertions, minimal data)
2. **Prioritize** — Start with critical controllers (auth, payments, invoices)
3. **Batch Changes** — Group files by module for logical commits
4. **Validate Early** — Run PHP syntax checks after each file
5. **Report Progress** — Commit after each module with detailed metrics

### Anti-Pattern Detection

Automatically detect and fix these lazy test patterns:

```php
// Pattern 1: Status-only tests
if (test has only `assertOk()` or `assertStatus()`) {
    ADD: Data verification from fixtures
    ADD: Structure verification (JSON keys, HTML content)
    ADD: Database verification (SELECT queries)
}

// Pattern 2: Empty JSON assertions
if (test has `assertJson([])`) {
    REPLACE: Decode JSON, verify array structure, check specific keys
    ADD: Database verification of source data
}

// Pattern 3: Zero assertions
if (test has no assertions or only `assertTrue(true)`) {
    ADD: Response verification (status, headers, content-type)
    ADD: Content verification (data from fixtures)
    ADD: Database verification (query results)
}

// Pattern 4: Minimal Arrange data
if (fakeDb insert has < 5 fields for entities that require 15+) {
    REPLACE: Use complete fixture data with all required fields
}
```

## Common Pitfalls to Avoid

### ❌ Don't Do This

```php
// DON'T: Log untrusted data directly (log injection vulnerability)
log_message('error', 'Failed: ' . $_POST['username']);

// DON'T: Trust user input for file paths (path traversal vulnerability)
$file = APPPATH . 'uploads/' . $_GET['filename'];
include($file);

// DON'T: Skip output encoding (XSS vulnerability)
<div><?php echo $user_input; ?></div>

// DON'T: Use loose comparison with user input
if ($_POST['user_type'] == 1) { // Can be bypassed with "1 "

// DON'T: Duplicate security logic across files
// (use helper functions instead)
```

### ✅ Do This Instead

```php
// DO: Sanitize before logging
log_message('error', 'Failed: ' . sanitize_for_logging($_POST['username']));

// DO: Validate file paths
$validation = validate_file_access($_GET['filename'], APPPATH . 'uploads/');
if ($validation['valid']) {
    $file = $validation['path'];
}

// DO: Always encode output
<div><?php echo html_escape($user_input); ?></div>

// DO: Use strict comparison
if ($_POST['user_type'] === '1') { // String comparison

// DO: Use helper functions for common operations
log_message('error', 'Upload: ' . sanitize_for_logging($filename));
```

## Code Review Focus Areas

When reviewing code, pay special attention to:

1. **Input handling** - Is all user input sanitized?
2. **Output encoding** - Is all output properly escaped?
3. **File operations** - Are file paths validated?
4. **Logging** - Is logged data sanitized?
5. **Code duplication** - Can repeated logic be extracted?
6. **Test coverage** - Are security-critical functions tested?

## Security Vulnerability Checklist

Before submitting code, verify:

- [ ] No XSS vulnerabilities (input sanitized, output encoded)
- [ ] No SQL injection (using Query Builder or prepared statements)
- [ ] No path traversal (file paths validated)
- [ ] No log injection (logged data sanitized)
- [ ] No header injection (headers sanitized)
- [ ] No LFI/RFI (file includes validated)
- [ ] File uploads are restricted (extension whitelist, no SVG)
- [ ] Authentication/authorization checks in place

## Additional Resources

- [CONTRIBUTING.md](../CONTRIBUTING.md) - General contribution guidelines
- [SECURITY.md](../SECURITY.md) - Security reporting process
- [.junie/guidelines.md](../.junie/guidelines.md) - Detailed development guidelines
- [CodeIgniter 3 Documentation](https://codeigniter.com/userguide3/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

## Summary

When working with InvoicePlane:

1. **Security First** - Defense in depth, multiple layers of protection
2. **DRY Principle** - Extract common logic into helper functions
3. **Test Everything** - Especially security-critical code
4. **Type Safety** - Use type hints and strict comparisons
5. **Follow Conventions** - PSR-12, test naming, file organization

Remember: InvoicePlane handles sensitive financial data. Security is not optional.

## Validation Workflow for Copilot Agents

Before completing any task, agents should:

### 1. Build Validation
```bash
# Always verify frontend builds succeed
npm run build
```
**Expected:** Build completes in 3-5 seconds with no errors.

### 2. Code Quality Checks
```bash
# Run PHP linters and formatters
composer check
```
**Expected:** No errors from Rector, PHPCS, or Pint.

### 3. PHP Syntax Check
```bash
# Validate PHP syntax of changed files
php -l path/to/changed/file.php
```
**Expected:** "No syntax errors detected"

### 4. Frontend Code Quality
```bash
# Check JavaScript/CSS formatting
npm run prettier:check
```
**Expected:** All files pass Prettier checks.

### 5. Test Changed Functionality
- If changing security functions, verify the security helper tests pass
- If changing frontend, manually test the UI to ensure it works
- If changing PHP backend, run syntax checks on all modified files

### 6. Review Security Checklist
- [ ] No XSS vulnerabilities (input sanitized, output encoded)
- [ ] No SQL injection (using Query Builder or prepared statements)
- [ ] No path traversal (file paths validated)
- [ ] No log injection (logged data sanitized)
- [ ] No header injection (headers sanitized)

### Common Validation Errors

**Error:** `vite: command not found` or `Cannot find module 'glob'`
**Fix:** Run `yarn install` to install all dependencies first.

**Error:** PHP syntax errors after editing
**Fix:** Verify your changes follow PSR-12 and use proper PHP 8.1+ syntax.

**Error:** Build succeeds but changes don't appear
**Fix:** Ensure you're editing source files in `resources/assets/`, not compiled files in `public/assets/`.


## Frontend Build and Asset Rules (Current)

- **Do run CLI verification commands** in this repository when changing frontend/backend behavior (`npm run build`, `php -l`, tests where available).
- **Sass is currently part of the build**; do not assume it has been removed.
- Vite outputs compiled assets to **`public/assets`**.
- Standardize JavaScript by moving inline scripts from PHP views into `resources/assets/js/modules/**`.
- Pass dynamic PHP values to JS via `data-*` attributes (or JSON script config blocks), not inline procedural script bodies.
- Keep jQuery route posts and existing behavior intact during extraction.
