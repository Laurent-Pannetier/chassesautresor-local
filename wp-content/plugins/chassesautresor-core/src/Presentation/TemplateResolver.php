<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

use InvalidArgumentException;

/** Resolve overridable presentation templates without reading from the historical theme. */
final class TemplateResolver {
    private string $templateDirectory;

    /** @var callable */
    private $themeLocator;

    public function __construct(string $templateDirectory, ?callable $themeLocator = null) {
        $this->templateDirectory = rtrim($templateDirectory, '/\\');
        $this->themeLocator = $themeLocator ?? static function (string $relativePath): string {
            return (string) locate_template('chassesautresor-core/' . $relativePath, false, false);
        };
    }

    public function resolve(string $template): string {
        $template = $this->normalize($template);
        $override = (string) call_user_func($this->themeLocator, $template);
        if ($override !== '' && is_file($override)) {
            return $override;
        }

        $fallback = $this->templateDirectory . '/' . $template;
        if (!is_file($fallback)) {
            throw new InvalidArgumentException('Unknown Core template: ' . $template);
        }

        return $fallback;
    }

    /** @param array<string,mixed> $viewModel */
    public function render(string $template, array $viewModel = []): string {
        $path = $this->resolve($template);
        ob_start();
        (static function (string $__path, array $__viewModel): void {
            $viewModel = $__viewModel;
            require $__path;
        })($path, $viewModel);

        return (string) ob_get_clean();
    }

    private function normalize(string $template): string {
        $template = str_replace('\\', '/', trim($template));
        if ($template === '' || str_starts_with($template, '/') || str_contains($template, '../')) {
            throw new InvalidArgumentException('Invalid Core template name.');
        }
        if (pathinfo($template, PATHINFO_EXTENSION) !== 'php') {
            $template .= '.php';
        }

        return $template;
    }
}
