<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Store statistics in the object cache and transient fallback consistently.
 */
class StatisticsCacheService
{
    /** @var callable */
    private $cacheGet;
    /** @var callable */
    private $cacheSet;
    /** @var callable */
    private $cacheDelete;
    /** @var callable */
    private $getTransient;
    /** @var callable */
    private $setTransient;
    /** @var callable */
    private $deleteTransient;

    public function __construct(
        ?callable $cacheGet = null,
        ?callable $cacheSet = null,
        ?callable $cacheDelete = null,
        ?callable $getTransient = null,
        ?callable $setTransient = null,
        ?callable $deleteTransient = null
    ) {
        $this->cacheGet = $cacheGet ?? 'wp_cache_get';
        $this->cacheSet = $cacheSet ?? 'wp_cache_set';
        $this->cacheDelete = $cacheDelete ?? 'wp_cache_delete';
        $this->getTransient = $getTransient ?? 'get_transient';
        $this->setTransient = $setTransient ?? 'set_transient';
        $this->deleteTransient = $deleteTransient ?? 'delete_transient';
    }

    /** @return mixed */
    public function get(string $scope, int $objectId, string $period)
    {
        [$key, $group] = $this->coordinates($scope, $objectId, $period);
        $value = ($this->cacheGet)($key, $group);
        return $value === false ? ($this->getTransient)($key) : $value;
    }

    /** @param mixed $value */
    public function put(string $scope, int $objectId, string $period, $value, int $ttl): void
    {
        [$key, $group] = $this->coordinates($scope, $objectId, $period);
        ($this->cacheSet)($key, $value, $group, $ttl);
        ($this->setTransient)($key, $value, $ttl);
    }

    public function clear(string $scope, int $objectId): void
    {
        if ($objectId <= 0 || !in_array($scope, ['chasse', 'enigme'], true)) {
            return;
        }
        foreach (StatisticsPeriodService::PERIODS as $period) {
            [$key, $group] = $this->coordinates($scope, $objectId, $period);
            ($this->cacheDelete)($key, $group);
            ($this->deleteTransient)($key);
        }
    }

    /** @return array{string,string} */
    private function coordinates(string $scope, int $objectId, string $period): array
    {
        $scope = $scope === 'chasse' ? 'chasse' : 'enigme';
        return ["{$scope}_stats_{$objectId}_{$period}", "{$scope}_stats"];
    }
}
