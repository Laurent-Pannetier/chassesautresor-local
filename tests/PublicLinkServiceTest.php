<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\PublicLinkService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/PublicLinkService.php';

final class PublicLinkServiceTest extends TestCase {
    private PublicLinkService $service;

    protected function setUp(): void {
        $this->service = new PublicLinkService();
    }

    public function testNormalizesOrganizerRowsAndSkipsInvalidEntries(): void {
        $links = $this->service->activeLinks([
            ['type_de_lien' => ['discord'], 'url_lien' => ' https://discord.example/test '],
            ['type_de_lien' => '', 'url_lien' => 'https://ignored.example'],
            ['type_de_lien' => 'facebook', 'url_lien' => null],
            'invalid',
        ]);

        $this->assertSame(['discord' => 'https://discord.example/test'], $links);
    }

    public function testUsesContextFieldsAndKeepsTheLastLinkOfEachType(): void {
        $links = $this->service->activeLinks([
            [
                'chasse_principale_liens_type' => 'site_web',
                'chasse_principale_liens_url' => 'https://first.example',
            ],
            [
                'chasse_principale_liens_type' => ['site_web'],
                'chasse_principale_liens_url' => 'https://last.example',
            ],
        ], 'chasse');

        $this->assertSame(['site_web' => 'https://last.example'], $links);
    }
}
