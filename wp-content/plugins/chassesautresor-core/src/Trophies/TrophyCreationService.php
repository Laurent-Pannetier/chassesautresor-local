<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Publish one active trophy from the short administrator form. */
final class TrophyCreationService
{
    /**
     * @param callable(array<string, mixed>): mixed $insertPost
     * @param callable(string, mixed, int): void $updateField
     */
    public function create(
        TrophyDraft $draft,
        int $authorId,
        callable $insertPost,
        callable $updateField
    ): int {
        $created = $insertPost([
            'post_type' => TrophyPostTypeRegistrar::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $draft->title,
            'post_excerpt' => $draft->description,
            'post_author' => max(0, $authorId),
        ]);
        $trophyId = is_numeric($created) ? (int) $created : 0;
        if ($trophyId <= 0) {
            return 0;
        }

        $fields = [
            'trophee_description' => $draft->description,
            'trophee_active' => 1,
            'trophee_scope' => $draft->scope,
            'trophee_trigger_type' => CodeEventTrophyTrigger::TYPE,
            CodeEventTrophyTrigger::FIELD => $draft->eventKey,
            'trophee_notification_enabled' => 1,
            'trophee_notification_title' => '',
            'trophee_notification_body' => '',
            'trophee_notification_priority' => 100,
            'trophee_notification_channel' => 'both',
        ];
        if ($draft->imageId > 0) {
            $fields['trophee_image'] = $draft->imageId;
        }
        if ($draft->huntId > 0) {
            $fields['trophee_chasse'] = $draft->huntId;
        }
        foreach ($fields as $name => $value) {
            $updateField($name, $value, $trophyId);
        }

        return $trophyId;
    }
}
