<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Registry used by the admin schema and event dispatcher. */
final class TrophyTriggerRegistry
{
    /** @var array<string, TrophyTriggerProvider> */
    private array $providers = [];

    /** @param TrophyTriggerProvider[]|null $providers */
    public function __construct(?array $providers = null)
    {
        $providers ??= [new CodeEventTrophyTrigger()];
        if (function_exists('apply_filters')) {
            $providers = (array) apply_filters('cat_trophy_trigger_providers', $providers);
        }
        foreach ($providers as $provider) {
            if ($provider instanceof TrophyTriggerProvider) {
                $this->providers[$provider->key()] = $provider;
            }
        }
    }

    /** @return array<string, string> */
    public function choices(): array
    {
        $choices = [];
        foreach ($this->providers as $provider) {
            $choices[$provider->key()] = $provider->label();
        }

        return $choices;
    }

    /** @return array<int, array<string, mixed>> */
    public function fields(): array
    {
        $fields = [];
        foreach ($this->providers as $provider) {
            array_push($fields, ...$provider->fields());
        }

        return $fields;
    }

    public function get(string $type): ?TrophyTriggerProvider
    {
        return $this->providers[$type] ?? null;
    }
}
