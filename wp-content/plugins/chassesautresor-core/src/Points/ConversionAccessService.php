<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

use ChassesAuTresor\Core\Relationships\OrganizerRepository;

/** Evaluate whether an organizer can open a new points conversion request. */
final class ConversionAccessService
{
    private ConversionService $conversions;
    private PointsService $points;
    private OrganizerRepository $organizers;

    public function __construct(
        ConversionService $conversions,
        PointsService $points,
        OrganizerRepository $organizers
    ) {
        $this->conversions = $conversions;
        $this->points = $points;
        $this->organizers = $organizers;
    }

    /** @return bool|string */
    public function resolve(int $userId)
    {
        $user = get_userdata($userId);
        $organizerRole = defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur';
        if (!$user || !in_array($organizerRole, (array) $user->roles, true)) {
            return __('Inscription en cours', 'chassesautresor-com');
        }

        $organizerId = $this->organizers->findIdForUser($userId) ?? 0;
        if ($organizerId <= 0) {
            return __('Erreur : organisateur non trouvé.', 'chassesautresor-com');
        }

        $requests = $this->conversions->getRequests($userId);
        $lastSettlement = null;
        foreach ($requests as $request) {
            if (($request['request_status'] ?? '') === 'pending') {
                return __('Demande déjà en cours', 'chassesautresor-com');
            }
            if (($request['request_status'] ?? '') === 'paid') {
                $settledAt = strtotime((string) ($request['settlement_date'] ?? $request['request_date'] ?? ''));
                $lastSettlement = max((int) $lastSettlement, (int) $settledAt);
            }
        }

        $minimumDate = strtotime('-30 days');
        if ($lastSettlement !== null && $lastSettlement > $minimumDate) {
            $remainingDays = (int) ceil(($lastSettlement - $minimumDate) / DAY_IN_SECONDS);

            return sprintf(
                /* translators: %d: remaining number of days. */
                __('Attendez encore %d jours', 'chassesautresor-com'),
                $remainingDays
            );
        }

        $minimum = (int) apply_filters('points_conversion_min', 500);
        if ($this->points->getBalance($userId) < $minimum) {
            return 'INSUFFICIENT_POINTS';
        }

        $iban = get_field('iban', $organizerId) ?: get_field('gagnez_de_largent_iban', $organizerId);
        $bic = get_field('bic', $organizerId) ?: get_field('gagnez_de_largent_bic', $organizerId);

        return $iban && $bic ? true : 'MISSING_BANK_DETAILS';
    }
}
