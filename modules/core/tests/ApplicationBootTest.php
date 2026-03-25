<?php

namespace Modules\Core\Tests;

use PHPUnit\Framework\TestCase;

class ApplicationBootTest extends TestCase
{
    public function test_laravel_bootstrap_exists(): void
    {
        self::assertFileExists(dirname(__DIR__, 3) . '/bootstrap/invoiceplane.php');
    }

    public function test_invoiceplane_entry_loads(): void
    {
        self::assertFileExists(dirname(__DIR__, 3) . '/bootstrap/aliases.php');
    }

    public function test_controller_dispatch_path_exists(): void
    {
        self::assertDirectoryExists(dirname(__DIR__, 2) . '/invoices/src/Controllers');
    }

    public function test_core_view_path_exists(): void
    {
        self::assertDirectoryExists(dirname(__DIR__) . '/resources/views');
    }
}
