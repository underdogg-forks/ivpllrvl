<?php

namespace Modules\Tasks\Tests;

use Modules\Tasks\Controllers\TasksAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(TasksAjaxController::class)]
class TasksAjaxControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(TasksAjaxController::class));
    }
}
