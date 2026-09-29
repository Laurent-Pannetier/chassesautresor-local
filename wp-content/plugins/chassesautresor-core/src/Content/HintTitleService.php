<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Build and identify generated hint titles.
 */
class HintTitleService
{
    public function buildPlaceholder(string $prefix, string $storedSlug, string $generatedSlug): string
    {
        return $prefix . ($storedSlug !== '' ? $storedSlug : $generatedSlug);
    }

    public function shouldRegenerate(string $currentTitle, string $defaultTitle, string $prefix): bool
    {
        if ($currentTitle === '' || ($defaultTitle !== '' && $currentTitle === $defaultTitle)) {
            return true;
        }

        if (preg_match('/^Indice #\d+$/', $currentTitle) === 1) {
            return true;
        }

        return $prefix !== '' && strpos($currentTitle, $prefix) === 0;
    }
}
