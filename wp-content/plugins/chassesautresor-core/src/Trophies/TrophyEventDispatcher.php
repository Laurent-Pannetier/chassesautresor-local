<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Evaluate every active trophy against one normalized application event. */
final class TrophyEventDispatcher
{
    public function __construct(
        private readonly TrophyDefinitionRepository $definitions,
        private readonly TrophyTriggerRegistry $triggers,
        private readonly TrophyAwardService $awards
    ) {
    }

    /** @return array<int, int[]> Trophy ID to newly-created award IDs. */
    public function dispatch(TrophyEvent $event): array
    {
        if ($event->userId <= 0 || trim($event->key) === '') {
            return [];
        }

        $result = [];
        foreach ($this->definitions->active() as $definition) {
            $trigger = $this->triggers->get($definition->triggerType);
            if ($trigger === null || !$trigger->matches($definition->triggerConfig, $event)) {
                continue;
            }
            $awardIds = $this->awards->award(
                $definition,
                $event->userId,
                $event->type . ':' . $event->key,
                $event->context
            );
            if ($awardIds !== []) {
                $result[$definition->id] = $awardIds;
            }
        }

        return $result;
    }
}
