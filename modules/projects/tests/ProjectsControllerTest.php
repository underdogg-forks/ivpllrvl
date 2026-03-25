<?php

namespace Modules\Projects\Tests;

use Modules\Projects\Controllers\ProjectsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ProjectsController::class)]
class ProjectsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(ProjectsController::class));
    }
}
