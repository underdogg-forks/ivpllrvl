#!/bin/bash
# Migration script to systematically migrate all remaining controller tests

echo "Starting migration of Core module controller tests..."

# List of test files to migrate
tests=(
    "EmailTemplatesControllerTest.php"
    "EmailTemplatesAjaxControllerTest.php"
    "FilterAjaxControllerTest.php"
    "ImportControllerTest.php"
    "LayoutControllerTest.php"
    "MailerControllerTest.php"
    "ReportsControllerTest.php"
    "SessionsControllerTest.php"
    "SettingsAjaxControllerTest.php"
    "SetupControllerTest.php"
    "TaxRatesControllerTest.php"
    "UploadControllerTest.php"
    "UsersAjaxControllerTest.php"
    "UsersControllerTest.php"
    "VersionsControllerTest.php"
)

for test in "${tests[@]}"; do
    echo "Processing: $test"
    if grep -q "getController()" "modules/core/tests/$test"; then
        echo "  - Still uses getController(), needs migration"
    else
        echo "  - Already migrated or no getController() calls"
    fi
done

echo "Migration plan created."
