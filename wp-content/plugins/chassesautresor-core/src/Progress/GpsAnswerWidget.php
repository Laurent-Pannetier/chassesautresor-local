<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Evaluate a manually selected GPS point against the configured target. */
final class GpsAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'gps';
    }

    public function evaluate(string $answer, array $configuration): array {
        $submitted = $this->coordinates($answer);
        $target = $this->coordinates((string) ($configuration['target_coordinates'] ?? ''));
        if ($submitted === null || $target === null) {
            return ['resultat' => 'faux', 'message' => ''];
        }

        $tolerance = max(1.0, (float) ($configuration['tolerance_meters'] ?? 25));
        $distance = $this->distanceInMeters($submitted, $target);

        return ['resultat' => $distance <= $tolerance ? 'bon' : 'faux', 'message' => ''];
    }

    /** @return array{0:float,1:float}|null */
    private function coordinates(string $value): ?array {
        if (preg_match('/^\s*(-?\d+(?:[.,]\d+)?)\s*[;|\s]\s*(-?\d+(?:[.,]\d+)?)\s*$/', $value, $matches) !== 1) {
            return null;
        }
        $latitude = (float) str_replace(',', '.', $matches[1]);
        $longitude = (float) str_replace(',', '.', $matches[2]);

        return abs($latitude) <= 90 && abs($longitude) <= 180 ? [$latitude, $longitude] : null;
    }

    /** @param array{0:float,1:float} $from @param array{0:float,1:float} $to */
    private function distanceInMeters(array $from, array $to): float {
        $earthRadius = 6371000;
        $latitudeDelta = deg2rad($to[0] - $from[0]);
        $longitudeDelta = deg2rad($to[1] - $from[1]);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($from[0])) * cos(deg2rad($to[0])) * sin($longitudeDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
