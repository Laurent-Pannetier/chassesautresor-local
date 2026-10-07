<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Validate and resolve optional image hotspots that reveal step answer widgets. */
final class RiddleStepHotspotService {
    public const MODE_ALWAYS = 'always';
    public const MODE_HOTSPOT = 'hotspot';

    private const MIN_SIZE = 5.0;
    private const MAX_PERCENT = 100.0;

    /**
     * @return array{
     *     mode: string,
     *     zone: ?array{x: float, y: float, w: float, h: float},
     *     label: string,
     *     active: bool
     * }|\WP_Error
     */
    public function validate(string $mode, string $zoneRaw, string $label, int $imageId) {
        $mode = $this->normalizeMode($mode);
        $label = $this->normalizeLabel($label);
        $zone = $this->parseZone($zoneRaw);

        if ($mode === self::MODE_HOTSPOT) {
            if ($imageId <= 0) {
                return new \WP_Error(
                    'hotspot_missing_image',
                    __(
                        'Le mode « Sur clic dans l’image » nécessite une image d’étape.',
                        'chassesautresor-com'
                    )
                );
            }
            if ($zone === null) {
                return new \WP_Error(
                    'hotspot_missing_zone',
                    __(
                        'Dessinez une zone cliquable sur l’image pour afficher le widget.',
                        'chassesautresor-com'
                    )
                );
            }
        }

        return [
            'mode' => $mode,
            'zone' => $zone,
            'label' => $label,
            'active' => $mode === self::MODE_HOTSPOT && $imageId > 0 && $zone !== null,
        ];
    }

    /**
     * @return array{
     *     mode: string,
     *     zone: ?array{x: float, y: float, w: float, h: float},
     *     zone_raw: string,
     *     label: string,
     *     active: bool
     * }
     */
    public function forStep(int $stepId, ?callable $getField = null): array {
        $getField = $getField ?? 'get_field';
        $imageId = (int) $getField('etape_image', $stepId);
        $mode = $this->normalizeMode((string) ($getField('etape_widget_affichage', $stepId) ?: ''));
        $zoneRaw = trim((string) ($getField('etape_hotspot_zone', $stepId) ?: ''));
        $label = $this->normalizeLabel((string) ($getField('etape_hotspot_label', $stepId) ?: ''));
        $zone = $this->parseZone($zoneRaw);

        return [
            'mode' => $mode,
            'zone' => $zone,
            'zone_raw' => $zone !== null ? $this->formatZone($zone) : '',
            'label' => $label,
            'active' => $mode === self::MODE_HOTSPOT && $imageId > 0 && $zone !== null,
        ];
    }

    /** @return array{x: float, y: float, w: float, h: float}|null */
    public function parseZone(string $raw): ?array {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        if (count($parts) !== 4) {
            return null;
        }

        $values = [];
        foreach ($parts as $part) {
            if (!is_numeric($part)) {
                return null;
            }
            $values[] = round((float) $part, 2);
        }

        [$x, $y, $w, $h] = $values;
        if (
            $w < self::MIN_SIZE
            || $h < self::MIN_SIZE
            || $x < 0.0
            || $y < 0.0
            || $x + $w > self::MAX_PERCENT + 0.01
            || $y + $h > self::MAX_PERCENT + 0.01
        ) {
            return null;
        }

        return [
            'x' => min($x, self::MAX_PERCENT - $w),
            'y' => min($y, self::MAX_PERCENT - $h),
            'w' => $w,
            'h' => $h,
        ];
    }

    /** @param array{x: float, y: float, w: float, h: float} $zone */
    public function formatZone(array $zone): string {
        return sprintf(
            '%s,%s,%s,%s',
            $this->formatPercent($zone['x']),
            $this->formatPercent($zone['y']),
            $this->formatPercent($zone['w']),
            $this->formatPercent($zone['h'])
        );
    }

    public function normalizeMode(string $mode): string {
        return $mode === self::MODE_HOTSPOT ? self::MODE_HOTSPOT : self::MODE_ALWAYS;
    }

    public function normalizeLabel(string $label): string {
        $label = trim($label);
        if ($label === '') {
            return __('Ouvrir le mécanisme', 'chassesautresor-com');
        }

        return function_exists('mb_substr') ? mb_substr($label, 0, 80) : substr($label, 0, 80);
    }

    public function persist(int $stepId, array $configuration, ?callable $updateField = null): void {
        $updateField = $updateField ?? 'update_field';
        $updateField('etape_widget_affichage', $configuration['mode'], $stepId);
        $updateField(
            'etape_hotspot_zone',
            $configuration['zone'] !== null ? $this->formatZone($configuration['zone']) : '',
            $stepId
        );
        $updateField('etape_hotspot_label', $configuration['label'], $stepId);
    }

    private function formatPercent(float $value): string {
        $formatted = number_format($value, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }
}
