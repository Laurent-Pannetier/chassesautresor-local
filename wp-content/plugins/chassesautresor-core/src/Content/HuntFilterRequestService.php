<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

final class HuntFilterRequestService
{
    /**
     * @param array<string, mixed> $request
     * @return array{statut?:string, cout?:string[], search?:string}
     */
    public static function normalize(array $request): array
    {
        $filters = [];
        $statuses = ['tous', 'en_cours', 'a_venir', 'termine'];
        $costs = ['gratuit', 'points'];

        if (isset($request['status']) && is_string($request['status'])) {
            $status = sanitize_text_field($request['status']);
            if (in_array($status, $statuses, true)) {
                $filters['statut'] = $status;
            }
        }

        if (array_key_exists('cost', $request)) {
            $rawCost = is_string($request['cost']) ? [$request['cost']] : $request['cost'];
            if (is_array($rawCost)) {
                $filters['cout'] = array_values(array_intersect($costs, array_map('strval', $rawCost)));
            }
        }

        if (array_key_exists('search', $request) && is_scalar($request['search'])) {
            $filters['search'] = sanitize_text_field((string) $request['search']);
        }

        return $filters;
    }
}
