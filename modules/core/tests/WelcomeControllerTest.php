<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\WelcomeController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WelcomeController::class)]
class WelcomeControllerTest extends TestCase
{
    #[Test]
    public function it_get_welcome_index_displays_welcome_page(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_get_welcome_index_loads_settings_model(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_get_welcome_index_loads_settings_helper(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_get_welcome_index_does_not_require_authentication(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }

    #[Test]
    public function it_welcome_page_displays_application_information(): void
    {
        $this->markTestIncomplete('HTTP test infrastructure needed');
    }
}
