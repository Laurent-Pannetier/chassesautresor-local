<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Read trophy definitions from the private WordPress post type. */
final class TrophyDefinitionRepository
{
    public function find(int $trophyId): ?TrophyDefinition
    {
        if (
            $trophyId <= 0
            || get_post_type($trophyId) !== TrophyPostTypeRegistrar::POST_TYPE
            || get_post_status($trophyId) !== 'publish'
        ) {
            return null;
        }

        return $this->hydrate($trophyId);
    }

    /** @return TrophyDefinition[] */
    public function active(): array
    {
        $query = new \WP_Query([
            'post_type' => TrophyPostTypeRegistrar::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'trophee_active',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => 'trophee_active',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        $definitions = [];
        foreach ((array) $query->posts as $trophyId) {
            $definition = $this->hydrate((int) $trophyId);
            if ($definition->active) {
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    private function hydrate(int $trophyId): TrophyDefinition
    {
        $title = (string) get_the_title($trophyId);
        $description = (string) get_post_field('post_excerpt', $trophyId);
        $triggerType = (string) $this->field(
            'trophee_trigger_type',
            $trophyId,
            CodeEventTrophyTrigger::TYPE
        );
        $scope = (string) $this->field('trophee_scope', $trophyId, 'user');
        if (!in_array($scope, ['user', 'team', 'both'], true)) {
            $scope = 'user';
        }
        $channel = (string) $this->field('trophee_notification_channel', $trophyId, 'both');
        if (!in_array($channel, ['center', 'overlay', 'both'], true)) {
            $channel = 'both';
        }

        return new TrophyDefinition(
            $trophyId,
            $title,
            $description,
            (int) get_post_thumbnail_id($trophyId),
            $scope,
            $triggerType,
            $this->triggerConfiguration($triggerType, $trophyId),
            (bool) $this->field('trophee_active', $trophyId, true),
            (bool) $this->field('trophee_notification_enabled', $trophyId, true),
            (string) $this->field('trophee_notification_title', $trophyId, ''),
            (string) $this->field('trophee_notification_body', $trophyId, ''),
            (int) $this->field('trophee_notification_priority', $trophyId, 100),
            $channel
        );
    }

    /** @return array<string, mixed> */
    private function triggerConfiguration(string $triggerType, int $trophyId): array
    {
        $provider = (new TrophyTriggerRegistry())->get($triggerType);
        if ($provider === null) {
            return [];
        }

        $configuration = [];
        foreach ($provider->fields() as $field) {
            $name = isset($field['name']) ? (string) $field['name'] : '';
            if ($name !== '') {
                $configuration[$name] = $this->field($name, $trophyId, '');
            }
        }

        return $configuration;
    }

    private function field(string $name, int $postId, mixed $default): mixed
    {
        $value = function_exists('get_field')
            ? get_field($name, $postId)
            : get_post_meta($postId, $name, true);

        return $value === '' || $value === null ? $default : $value;
    }
}
