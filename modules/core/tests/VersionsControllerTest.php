<?php

namespace Modules\Settings\Tests;

use Modules\Settings\Controllers\VersionsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(VersionsController::class)]
class VersionsControllerTest extends TestCase
{
    #[Test]
    public function it_get_versions_index_requires_authentication(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_get_versions_index_returns_version_list(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_get_versions_index_displays_pagination(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_versions_are_displayed_in_chronological_order(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_versions_show_application_version_numbers(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_versions_show_date_applied(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }
}
