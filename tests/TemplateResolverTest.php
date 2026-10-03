<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Presentation\TemplateResolver;
use PHPUnit\Framework\TestCase;

final class TemplateResolverTest extends TestCase {
    private string $directory;

    protected function setUp(): void {
        $this->directory = sys_get_temp_dir() . '/cat-core-templates-' . uniqid('', true);
        mkdir($this->directory . '/public', 0777, true);
        file_put_contents($this->directory . '/public/card.php', '<?php echo $viewModel["title"];');
    }

    protected function tearDown(): void {
        @unlink($this->directory . '/public/card.php');
        @rmdir($this->directory . '/public');
        @rmdir($this->directory);
    }

    public function testItFallsBackToThePluginTemplateAndPassesAnExplicitViewModel(): void {
        $resolver = new TemplateResolver($this->directory, static fn(string $template): string => '');

        self::assertSame($this->directory . '/public/card.php', $resolver->resolve('public/card'));
        self::assertSame('Chasse portable', $resolver->render('public/card', ['title' => 'Chasse portable']));
    }

    public function testAThemeCanOverrideAPluginTemplateFromTheDocumentedNamespace(): void {
        $override = $this->directory . '/override.php';
        file_put_contents($override, '<?php echo "override:" . $viewModel["title"];');
        $requested = '';
        $resolver = new TemplateResolver(
            $this->directory,
            static function (string $template) use ($override, &$requested): string {
                $requested = $template;
                return $override;
            }
        );

        self::assertSame('override:Test', $resolver->render('public/card.php', ['title' => 'Test']));
        self::assertSame('public/card.php', $requested);
        unlink($override);
    }

    /** @dataProvider invalidTemplateProvider */
    public function testItRejectsPathsOutsideTheTemplateContract(string $template): void {
        $this->expectException(InvalidArgumentException::class);
        (new TemplateResolver($this->directory, static fn(string $path): string => ''))->resolve($template);
    }

    /** @return array<int,array{string}> */
    public function invalidTemplateProvider(): array {
        return [[''], ['../secret'], ['/absolute.php'], ['public/../../secret.php']];
    }
}
