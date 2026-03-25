<?php

namespace Modules\Invoices\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(Modules\Core\Providers\ModuleResourceRegistry::class)]
class InvoiceModuleBootTest extends TestCase
{
    #[Test]
    public function it_resolves_invoice_views_from_the_real_module_layout(): void
    {
        /* Arrange */
        $resourceRegistry = new ModuleResourceRegistry();
        $modulesPath = dirname(__DIR__, 3) . '/modules/';

        /* Act */
        $viewDirectory = $resourceRegistry->resolveDirectoryForModuleAtLocation('invoices', 'views/', $modulesPath);
        $legacyViewDirectory = $resourceRegistry->resolveDirectoryForModuleAtLocation('invoices', 'legacy-views/', $modulesPath);

        /* Assert */
        self::assertIsString($viewDirectory);
        self::assertStringEndsWith('/modules/invoices/resources/views/', $viewDirectory);
        self::assertDirectoryExists($viewDirectory);
        self::assertNull($legacyViewDirectory);
    }
}
