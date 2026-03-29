#!/bin/bash

# Refactor InvoicesControllerTest.php to gold standard
sed -i 's/class InvoicesControllerTest extends TestCase/class InvoicesControllerTest extends ControllerTestCase/g' InvoicesControllerTest.php
sed -i '7a use Modules\Core\Testing\Traits\LoadsFixtures;\nuse Modules\Core\Testing\Traits\ProvidesTestData;\nuse Modules\Core\Testing\Traits\ProvidesAssertions;' InvoicesControllerTest.php
sed -i '17a \    use LoadsFixtures;\n    use ProvidesTestData;\n    use ProvidesAssertions;\n    \n    protected string $controllerClass = InvoicesController::class;' InvoicesControllerTest.php
sed -i 's/use Modules\\Core\\Testing\\TestCase;/use Modules\\Core\\Testing\\ControllerTestCase;/g' InvoicesControllerTest.php
sed -i 's/protected function fixtureTypes()/    \/**\n     * Define which fixture types this test needs\n     *\/\n    protected function fixtureTypes()/g' InvoicesControllerTest.php

# Refactor InvoicesAjaxControllerTest.php to gold standard
sed -i 's/class InvoicesAjaxControllerTest extends TestCase/class InvoicesAjaxControllerTest extends ControllerTestCase/g' InvoicesAjaxControllerTest.php
sed -i '7a use Modules\Core\Testing\Traits\LoadsFixtures;\nuse Modules\Core\Testing\Traits\ProvidesTestData;\nuse Modules\Core\Testing\Traits\ProvidesAssertions;' InvoicesAjaxControllerTest.php
sed -i '17a \    use LoadsFixtures;\n    use ProvidesTestData;\n    use ProvidesAssertions;\n    \n    protected string $controllerClass = InvoicesAjaxController::class;' InvoicesAjaxControllerTest.php
sed -i 's/use Modules\\Core\\Testing\\TestCase;/use Modules\\Core\\Testing\\ControllerTestCase;/g' InvoicesAjaxControllerTest.php

echo "Refactoring complete"
