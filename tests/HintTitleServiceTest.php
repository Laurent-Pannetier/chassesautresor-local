<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintTitleService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintTitleService.php';

class HintTitleServiceTest extends TestCase
{
    private HintTitleService $service;

    protected function setUp(): void
    {
        $this->service = new HintTitleService();
    }

    public function testPlaceholderPrefersStoredHuntSlug(): void
    {
        $this->assertSame(
            'clue-hunt-slug',
            $this->service->buildPlaceholder('clue-', 'hunt-slug', 'generated-slug')
        );
    }

    public function testPlaceholderUsesGeneratedSlugWhenStoredSlugIsEmpty(): void
    {
        $this->assertSame(
            'clue-generated-slug',
            $this->service->buildPlaceholder('clue-', '', 'generated-slug')
        );
    }

    /**
     * @dataProvider generatedTitleProvider
     */
    public function testGeneratedTitlesMustBeRegenerated(string $title, string $default, string $prefix): void
    {
        $this->assertTrue($this->service->shouldRegenerate($title, $default, $prefix));
    }

    public function generatedTitleProvider(): array
    {
        return [
            'empty title' => ['', '', 'clue-'],
            'configured default' => ['Indice', 'Indice', 'clue-'],
            'legacy numbered title' => ['Indice #12', '', 'clue-'],
            'placeholder prefix' => ['clue-hunt-slug', '', 'clue-'],
        ];
    }

    public function testCustomTitleIsPreserved(): void
    {
        $this->assertFalse($this->service->shouldRegenerate('Mon indice', 'Indice', 'clue-'));
    }
}
