<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\HuntPublicationStatusService;
use ChassesAuTresor\Core\Content\RiddleSolutionFileScheduler;
use ChassesAuTresor\Core\Relationships\HuntRiddleCacheSynchronizer;
use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;

/**
 * Recalculate and persist a hunt's functional and publication statuses.
 */
class HuntStatusUpdater {
    private static array $checked = [];

    public function refreshIfStale(int $huntId): bool {
        if (get_post_type($huntId) !== 'chasse' || isset(self::$checked[$huntId])) {
            return false;
        }
        self::$checked[$huntId] = true;
        $data = $this->read($huntId);
        $calculation = $data['calculation'];
        if (!(new HuntStatusService())->isStale(
            $data['current'],
            $data['validation'],
            $calculation[1],
            $calculation[2],
            $calculation[3],
            $calculation[4],
            $calculation[5],
            $calculation[6]
        )) {
            return false;
        }

        $this->refresh($huntId);
        return true;
    }

    public function refresh(int $huntId): ?string {
        if (get_post_type($huntId) !== 'chasse') {
            return null;
        }
        $data = $this->read($huntId);
        if ($data['validation'] === '') {
            return null;
        }
        $status = (new HuntStatusService())->calculate(...$data['calculation']);
        $riddleIds = get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId));
        if ($status === 'termine') {
            foreach ($riddleIds as $riddleId) {
                RiddleSolutionFileScheduler::schedule((int) $riddleId);
            }
        }
        update_field('chasse_cache_statut', $status, $huntId);
        (new HuntRiddleCacheSynchronizer())->synchronize($huntId, true, true);
        (new RiddleSystemStateUpdater())->refreshHunt($huntId, $status);
        do_action('chassesautresor_hunt_display_cache_clear_requested', $huntId);

        return $status;
    }

    public function synchronizePublication(int $huntId, ?string $validation = null): bool {
        if (get_post_type($huntId) !== 'chasse') {
            return false;
        }
        if ($validation !== null) {
            $validation = sanitize_text_field($validation);
            update_field('chasse_cache_statut_validation', $validation, $huntId);
        } else {
            $validation = (string) get_field('chasse_cache_statut_validation', $huntId);
        }
        if ($validation === '') {
            return false;
        }
        $wanted = (new HuntPublicationStatusService())->resolve($validation);
        if (get_post_status($huntId) === $wanted) {
            return false;
        }
        $result = wp_update_post(['ID' => $huntId, 'post_status' => $wanted]);
        return !is_wp_error($result);
    }

    /** @return array{validation:string,current:string,calculation:array{string,?int,?int,?int,int,bool,int,string}} */
    private function read(int $huntId): array {
        $validation = (string) get_field('chasse_cache_statut_validation', $huntId);
        $current = (string) get_field('chasse_cache_statut', $huntId);
        return [
            'validation' => $validation,
            'current' => $current !== '' ? $current : 'revision',
            'calculation' => [
                $validation,
                $this->timestamp(get_field('chasse_infos_date_debut', $huntId)),
                $this->timestamp(get_field('chasse_infos_date_fin', $huntId)),
                $this->timestamp(get_field('chasse_cache_date_decouverte', $huntId)),
                (int) get_field('chasse_infos_cout_points', $huntId),
                !empty(get_field('chasse_infos_duree_illimitee', $huntId)),
                (int) current_time('timestamp'),
                $current !== '' ? $current : 'revision',
            ],
        ];
    }

    private function timestamp($value): ?int {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }
        $timestamp = strtotime(str_replace('/', '-', (string) $value));
        return $timestamp === false ? null : $timestamp;
    }
}
