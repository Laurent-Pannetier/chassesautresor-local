<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntLinkMutationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntLinkMutationService.php';

final class HuntLinkMutationServiceTest extends TestCase {
    private HuntLinkMutationService $service;
    private array $storedLinks;
    private int $writeCount;

    protected function setUp(): void {
        $this->service = new HuntLinkMutationService();
        $this->storedLinks = [];
        $this->writeCount = 0;
    }

    public function testNormalizesAndPersistsValidLinks(): void {
        $result = $this->apply(json_encode([
            ['type_de_lien' => ' site ', 'url_lien' => 'https://example.com/path'],
            ['type_de_lien' => '', 'url_lien' => 'https://ignored.example'],
            'invalid',
        ]));

        $expected = [[
            'chasse_principale_liens_type' => 'site',
            'chasse_principale_liens_url' => 'https://example.com/path',
        ]];
        $this->assertNull($result['error']);
        $this->assertSame($expected, $result['value']);
        $this->assertSame($expected, $this->storedLinks);
        $this->assertSame(1, $this->writeCount);
    }

    public function testInvalidJsonIsRejectedWithoutWriting(): void {
        $result = $this->apply('{invalid');

        $this->assertSame('format_invalide', $result['error']);
        $this->assertSame([], $result['value']);
        $this->assertSame(0, $this->writeCount);
    }

    public function testEquivalentStoredLinksAvoidUnnecessaryWrite(): void {
        $this->storedLinks = [[
            'chasse_principale_liens_type' => 'site',
            'chasse_principale_liens_url' => 'https://example.com',
        ]];

        $result = $this->apply('[{"type_de_lien":"site","url_lien":"https://example.com"}]');

        $this->assertNull($result['error']);
        $this->assertSame(0, $this->writeCount);
    }

    public function testFalseUpdateIsAcceptedWhenStoredValueMatches(): void {
        $result = $this->apply(
            '[{"type_de_lien":"site","url_lien":"https://example.com"}]',
            false,
            true
        );

        $this->assertNull($result['error']);
    }

    public function testReportsPersistenceFailureWhenStoredValueDiffers(): void {
        $result = $this->apply(
            '[{"type_de_lien":"site","url_lien":"https://example.com"}]',
            false,
            false
        );

        $this->assertSame('echec_mise_a_jour_liens', $result['error']);
    }

    private function apply(string $json, $updateResult = true, bool $persist = true): array {
        return $this->service->apply(
            42,
            $json,
            static fn (string $type): string => trim($type),
            static fn (string $url): string => filter_var($url, FILTER_VALIDATE_URL) ? $url : '',
            fn (): array => $this->storedLinks,
            function (string $field, array $links) use ($updateResult, $persist) {
                ++$this->writeCount;
                if ($persist) {
                    $this->storedLinks = $links;
                }

                return $updateResult;
            }
        );
    }
}
