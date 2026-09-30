<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleImageProtectionLifecycle;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageProtectionLifecycle.php';

final class RiddleImageProtectionLifecycleTest extends TestCase {
    public function testRegistersSaveCronAndScheduleHooks(): void {
        $actions = [];
        $filters = [];
        RiddleImageProtectionLifecycle::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$actions): void {
                $actions[] = [$hook, $callback, $priority, $acceptedArgs];
            },
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$filters): void {
                $filters[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertCount(3, $actions);
        $this->assertSame('acf/save_post', $actions[0][0]);
        $this->assertSame(RiddleImageProtectionLifecycle::CRON_HOOK, $actions[2][0]);
        $this->assertSame('cron_schedules', $filters[0][0]);
    }
}
