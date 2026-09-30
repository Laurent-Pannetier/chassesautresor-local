<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Normalize public link rows shared by hunts and organizers.
 */
class PublicLinkService {
    /**
     * @param array<int, mixed> $rows
     * @return array<string, string>
     */
    public function activeLinks(array $rows, string $context = 'organisateur'): array {
        $links = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $typeValue = $row[$context . '_principale_liens_type']
                ?? $row['type_de_lien']
                ?? null;
            $url = $row[$context . '_principale_liens_url']
                ?? $row['url_lien']
                ?? null;
            $type = is_array($typeValue) ? ($typeValue[0] ?? '') : $typeValue;

            if (!is_string($type) || !is_string($url)) {
                continue;
            }

            $type = trim($type);
            $url = trim($url);
            if ($type !== '' && $url !== '') {
                $links[$type] = $url;
            }
        }

        return $links;
    }
}
