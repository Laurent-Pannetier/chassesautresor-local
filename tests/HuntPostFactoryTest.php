<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntPostFactory;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntPostFactory.php';

final class HuntPostFactoryTest extends TestCase {
    public function testInitialFieldsContainAllCreationDefaults(): void {
        $this->assertSame(
            [
                'chasse_principale_image' => 3902,
                'chasse_infos_date_debut' => '2026-10-01 12:00:00',
                'chasse_infos_date_fin' => '2028-10-01',
                'chasse_infos_duree_illimitee' => false,
                'chasse_infos_cout_points' => 0,
                'chasse_cache_statut' => 'revision',
                'chasse_cache_statut_validation' => 'creation',
                'chasse_cache_organisateur' => [12],
            ],
            (new HuntPostFactory())->getInitialFields(
                12,
                3902,
                '2026-10-01 12:00:00',
                '2028-10-01'
            )
        );
    }
}
