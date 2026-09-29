<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Query organizer relationships stored by WordPress and ACF.
 */
class OrganizerRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function findIdForUser(int $userId): ?int
    {
        $serializedId = '%"' . $this->wpdb->esc_like((string) $userId) . '"%';
        $organizerId = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT p.ID FROM {$this->wpdb->posts} p"
                . " INNER JOIN {$this->wpdb->postmeta} pm ON p.ID = pm.post_id"
                . " WHERE pm.meta_key = 'utilisateurs_associes' AND pm.meta_value LIKE %s"
                . " AND p.post_type = 'organisateur'"
                . " AND p.post_status IN ('publish','pending','draft') LIMIT 1",
                $serializedId
            )
        );

        return $organizerId ? (int) $organizerId : null;
    }
}
