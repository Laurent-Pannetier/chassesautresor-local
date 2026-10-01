<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntAccessService.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class HuntStatisticsAccessTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/access-functions.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/access-functions.php';
    }

    public function testAdministratorCanViewHuntStatisticsWithoutOrganizerAssociation(): void
    {
        $this->assertTrue(utilisateur_peut_voir_statistiques_chasse(12));
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability): bool
    {
        return $capability === 'manage_options';
    }
}
