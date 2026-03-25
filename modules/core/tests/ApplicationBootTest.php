<?php

namespace Modules\Core\Tests;

use Modules\Core\Testing\LaravelStyleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ApplicationBootTest extends LaravelStyleTestCase
{
    #[Test]
    public function it_has_the_laravel_bootstrap_file(): void
    {
        /* Arrange */
        $bootstrapFile = dirname(__DIR__, 3) . '/bootstrap/invoiceplane.php';

        /* Act */
        $bootstrapContents = file_get_contents($bootstrapFile);

        /* Assert */
        self::assertFileExists($bootstrapFile);
        self::assertIsString($bootstrapContents);
        self::assertStringContainsString('define(\'APPPATH\'', $bootstrapContents);
    }

    #[Test]
    public function it_bootstraps_the_laravel_application_without_breaking(): void
    {
        /* Arrange */
        $applicationBootstrapFile = dirname(__DIR__, 3) . '/bootstrap/app.php';

        /* Act */
        $application = require $applicationBootstrapFile;

        /* Assert */
        self::assertFileExists($applicationBootstrapFile);
        self::assertNull($application);
        self::assertNull($this->application);
    }

    #[Test]
    public function it_loads_the_core_module_view_file_without_breaking(): void
    {
        /* Arrange */
        $viewFile = dirname(__DIR__) . '/resources/views/alerts.php';
        $viewContents = '';

        /* Act */
        ob_start();
        include $viewFile;
        ob_end_clean();

        $viewContents = file_get_contents($viewFile);

        /* Assert */
        self::assertFileExists($viewFile);
        self::assertIsString($viewContents);
        self::assertStringContainsString('<div', $viewContents);
    }
}
