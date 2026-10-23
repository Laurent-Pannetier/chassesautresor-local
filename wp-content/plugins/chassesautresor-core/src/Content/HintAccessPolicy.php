<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Player access rules for a hint: consulted prerequisites and a relative delay.
 *
 * Prerequisites follow the riddle rule: every selected hint must already have
 * been consulted. A delay starts either at the player's first engagement or
 * when the last prerequisite was consulted.
 */
final class HintAccessPolicy {
    public const ANCHOR_NONE = 'aucun';
    public const ANCHOR_ENGAGEMENT = 'engagement';
    public const ANCHOR_PREREQUISITE = 'prerequis';
    public const REASON_OPEN = 'open';
    public const REASON_PREREQUISITE = 'prerequisite';
    public const REASON_DELAY = 'delay';
    public const ERROR_CYCLE = 'cycle_prerequis';
    public const ERROR_MISSING = 'missing_prerequisites';
    public const MAX_DELAY_MINUTES = 10080;
    private const SECONDS_PER_MINUTE = 60;
    private const SECONDS_PER_HOUR = 3600;
    private const SECONDS_PER_DAY = 86400;

    /**
     * @param array<int, mixed> $ids
     * @return int[]
     */
    public function normalizeIds(array $ids): array {
        $normalized = [];
        foreach ($ids as $id) {
            if (is_object($id)) {
                $id = $id->ID ?? 0;
            } elseif (is_array($id)) {
                $id = $id['ID'] ?? $id['id'] ?? 0;
            }
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param array<int, mixed> $requestedIds
     * @param array<int, mixed> $eligibleIds
     * @param array<int, array<int, mixed>> $prerequisiteGraph
     * @return array{error:?string,prerequisites:int[],delay_minutes:int,anchor:string}
     */
    public function normalize(
        int $hintId,
        array $requestedIds,
        array $eligibleIds,
        array $prerequisiteGraph,
        int $delayMinutes,
        string $anchor
    ): array {
        $eligible = array_fill_keys($this->normalizeIds($eligibleIds), true);
        $selected = [];
        foreach ($this->normalizeIds($requestedIds) as $id) {
            if ($id !== $hintId && isset($eligible[$id])) {
                $selected[] = $id;
            }
        }

        $minutes = max(0, min(self::MAX_DELAY_MINUTES, $delayMinutes));
        $anchor = $this->normalizeAnchor($anchor);
        if ($minutes === 0) {
            $anchor = self::ANCHOR_NONE;
        } elseif ($anchor === self::ANCHOR_NONE) {
            $anchor = self::ANCHOR_ENGAGEMENT;
        }

        if ($this->introducesCycle($hintId, $selected, $prerequisiteGraph)) {
            return $this->rejected(self::ERROR_CYCLE);
        }

        if ($anchor === self::ANCHOR_PREREQUISITE && $selected === []) {
            return $this->rejected(self::ERROR_MISSING);
        }

        return [
            'error' => null,
            'prerequisites' => $selected,
            'delay_minutes' => $minutes,
            'anchor' => $anchor,
        ];
    }

    /**
     * @param int[] $prerequisiteIds
     * @param array<int, int> $consultedAt Hint id => unix timestamp.
     * @return array{
     *     open:bool,
     *     reason:string,
     *     available_at:?int,
     *     missing:int[],
     *     delay_minutes:int,
     *     anchor:string
     * }
     */
    public function evaluate(
        int $now,
        array $prerequisiteIds,
        int $delayMinutes,
        string $anchor,
        ?int $engagementAt,
        array $consultedAt
    ): array {
        $prerequisiteIds = $this->normalizeIds($prerequisiteIds);
        $minutes = max(0, $delayMinutes);
        $anchor = $this->normalizeAnchor($anchor);
        $missing = [];
        foreach ($prerequisiteIds as $id) {
            if (!isset($consultedAt[$id])) {
                $missing[] = $id;
            }
        }

        $result = [
            'open' => false,
            'reason' => self::REASON_PREREQUISITE,
            'available_at' => null,
            'missing' => $missing,
            'delay_minutes' => $minutes,
            'anchor' => $anchor,
        ];
        if ($missing !== []) {
            return $result;
        }

        if ($minutes === 0 || $anchor === self::ANCHOR_NONE) {
            $result['open'] = true;
            $result['reason'] = self::REASON_OPEN;

            return $result;
        }

        $delaySeconds = $minutes * 60;
        if ($anchor === self::ANCHOR_ENGAGEMENT) {
            if ($engagementAt === null) {
                $result['reason'] = self::REASON_DELAY;

                return $result;
            }
            $result['available_at'] = $engagementAt + $delaySeconds;
        } else {
            if ($prerequisiteIds === []) {
                return $result;
            }
            $start = null;
            foreach ($prerequisiteIds as $id) {
                $stamp = (int) $consultedAt[$id];
                $start = $start === null ? $stamp : max($start, $stamp);
            }
            $result['available_at'] = $start + $delaySeconds;
        }

        if ($now >= (int) $result['available_at']) {
            $result['open'] = true;
            $result['reason'] = self::REASON_OPEN;
        } else {
            $result['reason'] = self::REASON_DELAY;
        }

        return $result;
    }

    /**
     * @param int[] $requestedIds
     * @param array<int, array<int, mixed>> $prerequisiteGraph
     */
    public function introducesCycle(int $hintId, array $requestedIds, array $prerequisiteGraph): bool {
        if ($hintId <= 0) {
            return false;
        }

        foreach ($this->normalizeIds($requestedIds) as $start) {
            if ($this->reaches($start, $hintId, $prerequisiteGraph, [])) {
                return true;
            }
        }

        return false;
    }

    public function countdownLabel(int $remainingSeconds): string {
        $remaining = max(0, $remainingSeconds);
        if ($remaining >= self::SECONDS_PER_DAY) {
            return intdiv($remaining, self::SECONDS_PER_DAY) . 'j';
        }
        if ($remaining >= self::SECONDS_PER_HOUR) {
            $hours = intdiv($remaining, self::SECONDS_PER_HOUR);
            $minutes = intdiv($remaining % self::SECONDS_PER_HOUR, self::SECONDS_PER_MINUTE);

            return $minutes > 0
                ? $hours . 'h' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT)
                : $hours . 'h';
        }

        $minutes = intdiv($remaining, self::SECONDS_PER_MINUTE);
        $seconds = $remaining % self::SECONDS_PER_MINUTE;

        return $minutes . ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
    }

    public function durationLabel(int $minutes): string {
        $minutes = max(0, $minutes);
        if ($minutes >= 1440) {
            $days = intdiv($minutes, 1440);
            $hours = intdiv($minutes % 1440, 60);

            return $hours > 0 ? $days . 'j' . $hours . 'h' : $days . 'j';
        }
        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $rest = $minutes % 60;

            return $rest > 0
                ? $hours . 'h' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT)
                : $hours . 'h';
        }

        return $minutes . 'm';
    }

    private function normalizeAnchor(string $anchor): string {
        return in_array($anchor, [self::ANCHOR_NONE, self::ANCHOR_ENGAGEMENT, self::ANCHOR_PREREQUISITE], true)
            ? $anchor
            : self::ANCHOR_NONE;
    }

    /**
     * @return array{error:string,prerequisites:array{},delay_minutes:int,anchor:string}
     */
    private function rejected(string $error): array {
        return [
            'error' => $error,
            'prerequisites' => [],
            'delay_minutes' => 0,
            'anchor' => self::ANCHOR_NONE,
        ];
    }

    /**
     * @param array<int, array<int, mixed>> $graph
     * @param array<int, bool> $seen
     */
    private function reaches(int $from, int $target, array $graph, array $seen): bool {
        if ($from === $target) {
            return true;
        }
        if (isset($seen[$from])) {
            return false;
        }
        $seen[$from] = true;
        foreach ($this->normalizeIds($graph[$from] ?? []) as $next) {
            if ($this->reaches($next, $target, $graph, $seen)) {
                return true;
            }
        }

        return false;
    }
}
