# TODO: Remaining Test Refactoring Work

## Overview

**Goal:** Refactor all 652 remaining test methods to gold standard pattern
**Gold Standard Reference:** `modules/projects/tests/ProjectsControllerTest.php`
**Status:** 251/903 tests complete (28%), 652 tests remaining (72%)

## What "Gold Standard" Means

Each test method must have:

1. **Trait imports** at class level:
   ```php
   use LoadsFixtures;
   use ProvidesTestData;
   use ProvidesAssertions;
   ```

2. **#region markers** for organization:
   - `// #region Authentication & Authorization Tests`
   - `// #region CRUD Operations Tests`
   - `// #region Validation Tests`
   - `// #region Security Tests`
   - `// #endregion`

3. **PHPDoc blocks** above the Act section showing complete POST data:
   ```php
   /**
    * Act: POST /users/form
    * POST data: {
    *   'user_name': 'John Doe',
    *   'user_email': 'john@example.com',
    *   [... all 18+ fields documented ...]
    *   'btn_submit': '1'
    * }
    * Expected: Create user, redirect to /users/index
    */
   ```

4. **Complete form data** using trait methods:
   ```php
   $completeData = $this->makeUserData(['btn_submit' => '1']);
   $response = $this->post('/users/form', $completeData);
   ```

5. **Trait-based assertions**:
   ```php
   $this->assertDatabaseHasRecord('ip_users', ['user_email' => 'john@example.com']);
   $this->assertRequiresAuthentication($response);
   ```

6. **Grammatical test names**:
   - ✅ `it_requires_authentication_to_display_users_index()`
   - ❌ `it_displays_users_index_requires_authentication()`

7. **Arrange-Act-Assert comments**:
   ```php
   /* Arrange */
   /* Act */
   /* Assert */
   ```

---

## Phase 1: Core Module - Critical Controllers (Priority 1)

**Target:** 4 files, 70 tests
**Estimated Effort:** 14 hours (~12 minutes per test)
**Impact:** High - authentication, settings, user management

### 1.1 UsersControllerTest.php
- **Status:** ⚠️ Needs refactoring
- **Tests:** 24
- **Controller Actions:**
  - `index($page)` - List users
  - `form($id)` - Create/edit user form (GET and POST)
  - `change_password($user_id)` - Change password (GET and POST)
  - `delete($id)` - Delete user
  - `delete_user_client($user_id, $user_client_id)` - Delete user-client link

**Refactoring Checklist:**
- [ ] Add trait imports (LoadsFixtures, ProvidesTestData, ProvidesAssertions)
- [ ] Change base class from `TestCase` to `ControllerTestCase`
- [ ] Replace `setUp()` with `fixtureTypes()` and `loadFixtures()`
- [ ] Add #region markers (Auth, CRUD, Validation, Security, Password, Delete, Custom Fields)
- [ ] Add PHPDoc blocks with complete POST data schemas for all POST tests
- [ ] Replace inline data arrays with `$this->makeUserData()`
- [ ] Replace basic assertions with trait methods
- [ ] Rename test methods for grammar (e.g., `it_requires_authentication_to_display_users_index`)
- [ ] Verify all 24 tests preserved

**Data Provider Needed:**
- `makeUserData()` - ✅ Already exists in ProvidesTestData trait

---

### 1.2 SessionsControllerTest.php
- **Status:** ⚠️ Needs refactoring
- **Tests:** 23
- **Controller Actions:**
  - `index()` - Login form display
  - `login()` - Process login (POST)
  - `authenticate($email, $password)` - Authentication logic
  - `logout()` - Logout user
  - `passwordreset($token)` - Password reset form and processing

**Refactoring Checklist:**
- [ ] Add trait imports
- [ ] Change base class to `ControllerTestCase`
- [ ] Replace `setUp()` with fixture loading pattern
- [ ] Add #region markers (Auth, Login, Logout, Password Reset, Security)
- [ ] Add PHPDoc blocks for login POST, password reset POST
- [ ] Create `makeLoginData()` in ProvidesTestData trait
- [ ] Create `makePasswordResetData()` in ProvidesTestData trait
- [ ] Replace assertions with trait methods
- [ ] Rename tests for grammar
- [ ] Verify all 23 tests preserved

**Data Providers Needed:**
- [ ] `makeLoginData()` - email, password, remember_me
- [ ] `makePasswordResetData()` - email, password, passwordv, token

---

### 1.3 SettingsControllerTest.php
- **Status:** ⚠️ Needs refactoring  
- **Tests:** 16
- **Controller Actions:**
  - `index()` - Settings form display and save (GET and POST)
  - `remove_logo($type)` - Remove logo image

**Refactoring Checklist:**
- [ ] Add trait imports
- [ ] Change base class to `ControllerTestCase`
- [ ] Replace fixture loading
- [ ] Add #region markers (Auth, Display, Update, Logo, Validation)
- [ ] Add PHPDoc blocks for settings POST (many fields!)
- [ ] Create `makeSettingsData()` in ProvidesTestData trait
- [ ] Replace assertions
- [ ] Rename tests
- [ ] Verify all 16 tests preserved

**Data Providers Needed:**
- [ ] `makeSettingsData()` - 50+ application settings fields

---

### 1.4 DashboardControllerTest.php
- **Status:** ⚠️ Needs refactoring
- **Tests:** 13  
- **Controller Actions:**
  - `index()` - Dashboard display

**Refactoring Checklist:**
- [ ] Add trait imports
- [ ] Change base class to `ControllerTestCase`
- [ ] Replace fixture loading
- [ ] Add #region markers (Auth, Display, Data Loading)
- [ ] PHPDoc blocks (mostly GET requests, document query params if any)
- [ ] Replace assertions
- [ ] Rename tests
- [ ] Verify all 13 tests preserved

**Data Providers Needed:**
- None (mostly GET requests)

---

## Phase 2: Core Module - Administrative Controllers (Priority 2)

**Target:** 7 files, 109 tests
**Estimated Effort:** 22 hours
**Impact:** Medium - admin features

### 2.1 TaxRatesControllerTest.php
- **Tests:** 14
- **Actions:** `index()`, `form($id)`, `delete($id)`
- **Data Provider:** [ ] `makeTaxRateData()`

### 2.2 EmailTemplatesControllerTest.php
- **Tests:** 17
- **Actions:** `index()`, `form($id)`, `delete($id)`
- **Data Provider:** [ ] `makeEmailTemplateData()`

### 2.3 CustomFieldsControllerTest.php
- **Tests:** 19
- **Actions:** `index()`, `table()`, `form($id)`, `delete($id)`
- **Data Provider:** [ ] `makeCustomFieldData()`

### 2.4 CustomValuesControllerTest.php
- **Tests:** 17
- **Actions:** `index()`, `field($id)`, `edit($id)`, `create()`, `delete($id)`
- **Data Provider:** [ ] `makeCustomValueData()`

### 2.5 ImportControllerTest.php
- **Tests:** 16
- **Actions:** `index()`, `form()`, `delete($id)`
- **Data Provider:** [ ] `makeImportData()`

### 2.6 MailerControllerTest.php
- **Tests:** 17
- **Actions:** `invoice($id)`, `quote($id)`, `send_invoice($id)`, `send_quote($id)`
- **Data Provider:** [ ] `makeMailerData()`

### 2.7 ReportsControllerTest.php
- **Tests:** 15
- **Actions:** 5 report methods
- **Data Provider:** None (GET requests with query params)

---

## Phase 3: Core Module - Utility Controllers (Priority 3)

**Target:** 8 files, 123 tests
**Estimated Effort:** 25 hours
**Impact:** Medium-Low - AJAX and utility endpoints

### 3.1 FilterAjaxControllerTest.php
- **Tests:** 16
- **Actions:** 10 filter methods (all AJAX GET)

### 3.2 UsersAjaxControllerTest.php
- **Tests:** 31
- **Actions:** 6 AJAX methods
- **Data Providers:** [ ] `makeUserClientData()`

### 3.3 EmailTemplatesAjaxControllerTest.php
- **Tests:** 9
- **Actions:** `get_content()` (AJAX)

### 3.4 SettingsAjaxControllerTest.php
- **Tests:** 6
- **Actions:** `get_cron_key()` (AJAX)

### 3.5 LayoutControllerTest.php
- **Tests:** 17
- **Actions:** `buffer()`, `set()`, `render()`, `load_view()`

### 3.6 UploadControllerTest.php
- **Tests:** 35
- **Actions:** File upload/download/delete methods
- **Data Providers:** [ ] `makeFileUploadData()`

### 3.7 VersionsControllerTest.php
- **Tests:** 8
- **Actions:** `index()`

### 3.8 WelcomeControllerTest.php
- **Tests:** 8
- **Actions:** `index()`

---

## Phase 4: Core Module - Setup Controller (Priority 4)

**Target:** 1 file, 32 tests
**Estimated Effort:** 6 hours
**Impact:** Low - installation/setup (rarely used)

### 4.1 SetupControllerTest.php
- **Tests:** 32
- **Actions:** 9 setup wizard steps
- **Data Providers:** [ ] `makeSetupData()`, `makeDatabaseConfig()`, `makeAdminUser()`

---

## Phase 5: Clients Module (Priority 5)

**Target:** 11 files, 179 tests
**Estimated Effort:** 36 hours  
**Impact:** High - client-facing features

### 5.1 ClientsControllerTest.php
- **Tests:** 21
- **Actions:** CRUD for clients
- **Data Provider:** ✅ `makeClientData()` - already exists

### 5.2 ClientsAjaxControllerTest.php
- **Tests:** 11
- **AJAX endpoints**

### 5.3 ViewControllerTest.php
- **Tests:** 32
- **Client portal views**

### 5.4 GetControllerTest.php
- **Tests:** 5
- **Client data retrieval**

### 5.5 GuestControllerTest.php
- **Tests:** 4
- **Guest access**

### 5.6 InvoicesController (Clients).php
- **Tests:** 23
- **Client invoice management**

### 5.7 QuotesController (Clients).php
- **Tests:** 28
- **Client quote management**

### 5.8 PaymentsController (Clients).php
- **Tests:** 16
- **Client payment management**

### 5.9 PaymentInformationControllerTest.php
- **Tests:** 16
- **Payment details**

### 5.10 UserClientsControllerTest.php
- **Tests:** 23
- **User-client relationships**

### 5.11 ClientModuleBootTest.php
- **Tests:** 0 (module boot test)

---

## Phase 6: Invoices Module (Priority 6)

**Target:** 6 files, 107 tests
**Estimated Effort:** 21 hours
**Impact:** High - core financial feature

### 6.1 InvoicesControllerTest.php  
- **Tests:** 28
- **Actions:** 15 invoice actions
- **Data Provider:** ✅ `makeInvoiceData()` - already exists

### 6.2 InvoicesAjaxControllerTest.php
- **Tests:** 24
- **AJAX endpoints**

### 6.3 RecurringControllerTest.php
- **Tests:** 20
- **Partially refactored** - needs completion

### 6.4 InvoiceGroupsControllerTest.php
- **Tests:** 18
- **Partially refactored** - needs completion

### 6.5 CronControllerTest.php
- **Tests:** 16
- **Partially refactored** - needs completion

### 6.6 InvoiceModuleBootTest.php
- **Tests:** 1 (module boot)

---

## Phase 7: Core Module - Remaining Tests (Priority 7)

**Target:** 3 files, 13 tests
**Estimated Effort:** 3 hours

### 7.1 ModuleResourceRegistryTest.php
- **Tests:** 7

### 7.2 ModuleServiceProviderTest.php
- **Tests:** 6

### 7.3 ApplicationBootTest.php
- **Tests:** 0 (application boot test)

---

## Summary Statistics

| Phase | Module | Files | Tests | Hours | Priority |
|-------|--------|-------|-------|-------|----------|
| 1 | Core (Critical) | 4 | 70 | 14 | High |
| 2 | Core (Admin) | 7 | 109 | 22 | Medium |
| 3 | Core (Utility) | 8 | 123 | 25 | Medium-Low |
| 4 | Core (Setup) | 1 | 32 | 6 | Low |
| 5 | Clients | 11 | 179 | 36 | High |
| 6 | Invoices | 6 | 107 | 21 | High |
| 7 | Core (Misc) | 3 | 13 | 3 | Low |
| **TOTAL** | **7 modules** | **40 files** | **652 tests** | **130 hrs** | - |

---

## Data Providers To Create

Add these methods to `modules/core/src/Testing/Traits/ProvidesTestData.php`:

### Authentication & Users
- [x] `makeUserData()` - ✅ Already exists
- [ ] `makeLoginData()`
- [ ] `makePasswordResetData()`

### Clients & Projects  
- [x] `makeClientData()` - ✅ Already exists
- [x] `makeProjectData()` - ✅ Already exists
- [x] `makeTaskData()` - ✅ Already exists

### Financial
- [x] `makeInvoiceData()` - ✅ Already exists
- [x] `makeQuoteData()` - ✅ Already exists
- [x] `makePaymentData()` - ✅ Already exists
- [x] `makeProductData()` - ✅ Already exists

### Administrative
- [ ] `makeSettingsData()`
- [ ] `makeTaxRateData()`
- [ ] `makeEmailTemplateData()`
- [ ] `makeCustomFieldData()`
- [ ] `makeCustomValueData()`
- [ ] `makeImportData()`
- [ ] `makeMailerData()`

### Setup & Misc
- [ ] `makeSetupData()`
- [ ] `makeDatabaseConfig()`
- [ ] `makeAdminUserData()`
- [ ] `makeFileUploadData()`
- [ ] `makeUserClientData()`

---

## Execution Strategy

### Option A: Sequential by Priority (Recommended)
Complete each phase fully before moving to next:
- Week 1: Phase 1 (Core Critical)
- Week 2: Phase 5 (Clients)
- Week 3: Phase 6 (Invoices)  
- Week 4: Phases 2-4, 7 (Remaining)

### Option B: Parallel by Controller Type
Group similar controllers across modules:
- Batch 1: All index/list methods (GET)
- Batch 2: All form methods (GET)
- Batch 3: All create methods (POST)
- Batch 4: All update methods (POST)
- Batch 5: All delete methods (POST)
- Batch 6: All AJAX methods

### Option C: Module Complete
Finish entire modules one at a time:
- Advantage: See complete module progress
- Disadvantage: Lower priority modules get done last

---

## Progress Tracking

Create a progress file at `/tmp/refactoring_progress.json`:

```json
{
  "last_updated": "2026-03-29",
  "total_tests": 903,
  "refactored": 251,
  "remaining": 652,
  "percent_complete": 28,
  "current_phase": 1,
  "current_file": "UsersControllerTest.php",
  "phases": {
    "1": { "complete": false, "tests_done": 0, "tests_total": 70 },
    "2": { "complete": false, "tests_done": 0, "tests_total": 109 },
    ...
  }
}
```

---

## Quality Checklist (Per File)

Before marking a file complete, verify:

- [ ] All original test methods preserved (count matches)
- [ ] All tests have #region markers
- [ ] All POST/PUT tests have PHPDoc with complete data schemas
- [ ] All data arrays replaced with trait method calls
- [ ] All basic assertions replaced with trait methods
- [ ] All test names follow grammar rules
- [ ] File has PHP syntax check passing: `php -l <file>`
- [ ] File matches gold standard pattern
- [ ] No test deletions (compare with git)
- [ ] Arrange-Act-Assert comments present

---

## Common Pitfalls to Avoid

1. **Don't delete tests** - Refactor in place, preserve all test logic
2. **Don't skip PHPDoc** - Document all POST data completely
3. **Don't use partial data** - Always submit complete forms
4. **Don't mix patterns** - Use traits consistently
5. **Don't forget #regions** - Organize tests logically
6. **Don't rush** - 12 minutes per test is realistic
7. **Don't automate blindly** - Manual review required
8. **Don't skip syntax checks** - Verify PHP parses correctly

---

## Completion Criteria

A module/file is "DONE" when:

1. ✅ All tests refactored to gold standard
2. ✅ Zero test deletions
3. ✅ PHP syntax check passes
4. ✅ Code review completed
5. ✅ Committed to branch with descriptive message
6. ✅ Progress updated in PR description

---

**Document Version:** 1.0  
**Created:** 2026-03-29  
**Last Updated:** 2026-03-29  
**Status:** Ready for execution
