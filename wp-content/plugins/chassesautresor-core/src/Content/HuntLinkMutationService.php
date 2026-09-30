<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Normalize and persist the editable external links of a hunt.
 */
class HuntLinkMutationService {
    /**
     * @param callable(string): string $sanitizeType
     * @param callable(string): string $sanitizeUrl
     * @param callable(string, int): mixed $getField
     * @param callable(string, mixed, int): mixed $updateField
     * @return array{error:?string,value:array<int, array<string, string>>}
     */
    public function apply(
        int $huntId,
        string $json,
        callable $sanitizeType,
        callable $sanitizeUrl,
        callable $getField,
        callable $updateField
    ): array {
        $submitted = json_decode(stripslashes($json), true);
        if (!is_array($submitted)) {
            return ['error' => 'format_invalide', 'value' => []];
        }

        $links = $this->normalizeSubmittedLinks($submitted, $sanitizeType, $sanitizeUrl);
        $current = $this->normalizeStoredLinks(
            $getField('chasse_principale_liens', $huntId),
            $sanitizeType,
            $sanitizeUrl
        );
        if ($current === $links) {
            return ['error' => null, 'value' => $links];
        }

        $updated = $updateField('chasse_principale_liens', $links, $huntId);
        $stored = $this->normalizeStoredLinks(
            $getField('chasse_principale_liens', $huntId),
            $sanitizeType,
            $sanitizeUrl
        );

        return [
            'error' => $updated === false && $stored !== $links
                ? 'echec_mise_a_jour_liens'
                : null,
            'value' => $links,
        ];
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, string>>
     */
    private function normalizeSubmittedLinks(
        array $items,
        callable $sanitizeType,
        callable $sanitizeUrl
    ): array {
        return $this->normalizeLinks($items, 'type_de_lien', 'url_lien', $sanitizeType, $sanitizeUrl);
    }

    /**
     * @param mixed $items
     * @return array<int, array<string, string>>
     */
    private function normalizeStoredLinks(
        $items,
        callable $sanitizeType,
        callable $sanitizeUrl
    ): array {
        if (!is_array($items)) {
            return [];
        }

        return $this->normalizeLinks(
            array_values($items),
            'chasse_principale_liens_type',
            'chasse_principale_liens_url',
            $sanitizeType,
            $sanitizeUrl
        );
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, string>>
     */
    private function normalizeLinks(
        array $items,
        string $typeKey,
        string $urlKey,
        callable $sanitizeType,
        callable $sanitizeUrl
    ): array {
        $links = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = $sanitizeType((string) ($item[$typeKey] ?? ''));
            $url = $sanitizeUrl((string) ($item[$urlKey] ?? ''));
            if ($type !== '' && $url !== '') {
                $links[] = [
                    'chasse_principale_liens_type' => $type,
                    'chasse_principale_liens_url' => $url,
                ];
            }
        }

        return $links;
    }
}
