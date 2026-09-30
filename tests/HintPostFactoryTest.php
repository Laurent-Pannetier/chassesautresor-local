<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintPostFactory;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintPostFactory.php';

final class HintPostFactoryTest extends TestCase {
    private HintPostFactory $factory;
    private array $initialState;

    protected function setUp(): void {
        $this->factory = new HintPostFactory();
        $this->initialState = [
            'post_status' => 'pending',
            'availability' => 'immediate',
            'availability_timestamp' => 123,
            'points_cost' => 0,
            'complete' => false,
            'system_state' => 'desactive',
        ];
    }

    public function testInitialHuntFieldsContainCreationDefaults(): void {
        $this->assertSame(
            [
                'indice_cible_type' => 'chasse',
                'indice_chasse_linked' => 12,
                'indice_disponibilite' => 'immediate',
                'indice_date_disponibilite' => '2026-10-01 12:00:00',
                'indice_cout_points' => 0,
                'indice_cache_complet' => false,
                'indice_cache_etat_systeme' => 'desactive',
            ],
            $this->factory->getInitialFields(
                12,
                'chasse',
                12,
                $this->initialState,
                '2026-10-01 12:00:00'
            )
        );
    }

    public function testInitialRiddleFieldsIncludeRiddleAndParentHunt(): void {
        $fields = $this->factory->getInitialFields(
            55,
            'enigme',
            12,
            $this->initialState,
            '2026-10-01 12:00:00'
        );

        $this->assertSame('enigme', $fields['indice_cible_type']);
        $this->assertSame(12, $fields['indice_chasse_linked']);
        $this->assertSame(55, $fields['indice_enigme_linked']);
    }
}
