<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddlePostFactory;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddlePostFactory.php';

class RiddlePostFactoryTest extends TestCase {
    public function testInitialFieldsContainRiddleCreationDefaults(): void {
        $factory = new RiddlePostFactory();

        $this->assertSame(
            [
                'enigme_chasse_associee' => 12,
                'enigme_organisateur_associe' => 34,
                'enigme_tentative_cout_points' => 0,
                'enigme_tentative_max' => 5,
                'enigme_reponse_casse' => true,
                'enigme_acces_condition' => 'immediat',
                'enigme_acces_pre_requis' => [],
                'enigme_mode_validation' => 'automatique',
                'enigme_acces_date' => '2026-10-30 12:00:00',
            ],
            $factory->getInitialFields(12, 34, '2026-10-30 12:00:00')
        );
    }
}
