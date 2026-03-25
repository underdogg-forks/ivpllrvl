<?php

namespace Modules\Core\Testing;

use PHPUnit\Framework\TestCase;

abstract class LaravelStyleTestCase extends TestCase
{
    protected mixed $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = require dirname(__DIR__, 3) . '/bootstrap/app.php';
    }
}
