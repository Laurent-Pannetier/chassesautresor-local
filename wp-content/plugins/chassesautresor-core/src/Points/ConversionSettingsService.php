<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

final class ConversionSettingsService
{
    private const DEFAULT_RATE = 85.0;
    private const HISTORY_LIMIT = 10;

    public function initialize(): void
    {
        if (get_option('taux_conversion') === false) {
            update_option('taux_conversion', self::DEFAULT_RATE);
        }
    }

    public function getRate(): float
    {
        return (float) get_option('taux_conversion', self::DEFAULT_RATE);
    }

    public function updateRate(float $rate): bool
    {
        if ($rate <= 0) {
            return false;
        }

        $history = get_option('historique_taux_conversion', []);
        $history = is_array($history) ? $history : [];
        $history[] = [
            'date_taux_conversion' => current_time('mysql'),
            'valeur_taux_conversion' => $rate,
        ];

        update_option('taux_conversion', $rate);
        update_option('historique_taux_conversion', array_slice($history, -self::HISTORY_LIMIT));

        return true;
    }
}
