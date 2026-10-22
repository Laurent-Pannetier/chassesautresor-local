<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Generic extension point: application code dispatches a stable named event. */
final class CodeEventTrophyTrigger implements TrophyTriggerProvider
{
    public const TYPE = 'code_event';
    public const FIELD = 'trophee_trigger_event_key';

    public function key(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('Événement fourni par le code', 'chassesautresor-com');
    }

    public function fields(): array
    {
        return [
            [
                'key' => 'field_trophee_trigger_event_key',
                'label' => __('Clé de l’événement', 'chassesautresor-com'),
                'name' => self::FIELD,
                'type' => 'text',
                'instructions' => __(
                    'Identifiant stable envoyé par le code. Aucun scénario n’est activé automatiquement.',
                    'chassesautresor-com'
                ),
                'required' => true,
                'maxlength' => 120,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_trophee_trigger_type',
                            'operator' => '==',
                            'value' => self::TYPE,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function matches(array $configuration, TrophyEvent $event): bool
    {
        $configured = trim((string) ($configuration[self::FIELD] ?? ''));

        return $event->type === self::TYPE && $configured !== '' && hash_equals($configured, $event->key);
    }
}
