<?php

namespace Modules\Core\Testing;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected mixed $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = require base_path('bootstrap/app.php');
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 4);

        return $path === '' ? $basePath : $basePath . '/' . ltrim($path, '/');
    }
}

if (! function_exists('trans')) {
    function trans(string $key): string
    {
        return $key;
    }
}
