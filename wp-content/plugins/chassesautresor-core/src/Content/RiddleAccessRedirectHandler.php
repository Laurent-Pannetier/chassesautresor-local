<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Progress\HuntCompletionHookHandler;
use ChassesAuTresor\Core\Progress\RiddleParticipationPolicyService;
use ChassesAuTresor\Core\Relationships\HuntRiddleCacheSynchronizer;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Enforce access and engagement redirects on single riddle pages.
 */
final class RiddleAccessRedirectHandler {
    public static function register(callable $addAction): void {
        $addAction('template_redirect', [self::class, 'handle']);
    }

    public static function handle(): void {
        if (!is_singular('enigme')) {
            return;
        }

        global $wpdb;

        $riddleId = get_queried_object_id();
        $userId = get_current_user_id();
        $editionActive = utilisateur_peut_modifier_post($riddleId);
        $relationships = new RelationshipService();
        $huntId = (int) $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId));

        if (!is_user_logged_in()) {
            $gated = self::gatedEntryUrl($huntId, 0);
            if ($gated !== '') {
                wp_safe_redirect($gated);
                exit;
            }

            $url = $huntId > 0 ? get_permalink($huntId) : home_url('/');
            wp_redirect($url);
            exit;
        }

        $huntEngaged = $huntId > 0 && $userId > 0
            && CoreServiceFactory::huntEngagement($wpdb)->isEngaged($userId, $huntId);

        if ($huntId > 0) {
            $authorized = current_user_can('manage_options')
                || current_user_can(defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur')
                || current_user_can(
                    defined('ROLE_ORGANISATEUR_CREATION')
                        ? ROLE_ORGANISATEUR_CREATION
                        : 'organisateur_creation'
                );
            (new HuntRiddleCacheSynchronizer())->maybeSynchronize($huntId, $authorized);
        }

        if (
            $huntEngaged
            && !CoreServiceFactory::riddleEngagement($wpdb)->isEngaged($userId, $riddleId)
            && self::canEngage($userId, $riddleId)
        ) {
            CoreServiceFactory::riddleEngagement($wpdb)->ensureEngaged($userId, $riddleId, current_time('mysql'));

            if (get_field('enigme_mode_validation', $riddleId) === 'aucune') {
                HuntCompletionHookHandler::handle($userId, $riddleId);
            }
        }

        if (!self::isVisible($userId, $riddleId, $huntId)) {
            $gated = self::gatedEntryUrl($huntId, $userId);
            if ($gated !== '') {
                wp_safe_redirect($gated);
                exit;
            }

            $fallback_url = $huntId > 0 ? get_permalink($huntId) : home_url('/');
            wp_redirect($fallback_url);
            exit;
        }

        $systemStatus = get_field('enigme_cache_etat_systeme', $riddleId) ?? 'accessible';
        $accessCondition = get_field('enigme_acces_condition', $riddleId) ?? 'immediat';
        $huntFinished = $huntId > 0 && get_field('chasse_cache_statut', $huntId) === 'termine';

        if (
            $accessCondition === 'pre_requis'
            && $systemStatus === 'bloquee_pre_requis'
            && !self::prerequisitesMet($userId, $riddleId)
            && !$editionActive
        ) {
            $url = $huntId > 0 ? get_permalink($huntId) : home_url('/');
            wp_safe_redirect($url);
            exit;
        }

        if (
            $systemStatus !== 'accessible'
            && $systemStatus !== 'bloquee_pre_requis'
            && !($huntFinished && $systemStatus === 'bloquee_date')
            && !$editionActive
        ) {
            $url = $huntId > 0 ? get_permalink($huntId) : home_url('/');
            wp_safe_redirect($url);
            exit;
        }

        (new CompletionCacheManager())->refresh($riddleId);
    }

    private static function gatedEntryUrl(int $huntId, int $userId): string
    {
        if (!class_exists(\ChassesAuTresor\Core\Site\PlayerEnigmasAccessHandler::class)) {
            return '';
        }

        return \ChassesAuTresor\Core\Site\PlayerEnigmasAccessHandler::entryRedirectUrl($huntId, $userId);
    }

    private static function prerequisitesMet(int $userId, int $riddleId): bool
    {
        global $wpdb;
        $relationships = new RelationshipService();
        $ids = $relationships->normalizeIds((array) get_field('enigme_acces_pre_requis', $riddleId));
        $condition = (string) (get_field('enigme_acces_condition', $riddleId) ?? 'immediat');
        return CoreServiceFactory::huntProgress($wpdb)->areRiddlePrerequisitesMet($userId, $ids, $condition);
    }

    private static function canEngage(int $userId, int $riddleId): bool
    {
        global $wpdb;
        $systemStatus = (string) (get_field('enigme_cache_etat_systeme', $riddleId) ?: 'invalide');
        $status = (string) (CoreServiceFactory::huntProgress($wpdb)->getRiddleStatus($userId, $riddleId)
            ?? 'non_commencee');
        $prerequisitesMet = $systemStatus === 'bloquee_pre_requis'
            && self::prerequisitesMet($userId, $riddleId);
        return CoreServiceFactory::huntProgress($wpdb)->canEngageRiddle(
            $systemStatus,
            strtolower(remove_accents($status)),
            $prerequisitesMet
        );
    }

    private static function isVisible(int $userId, int $riddleId, int $huntId): bool
    {
        global $wpdb;
        $status = (string) (CoreServiceFactory::huntProgress($wpdb)->getRiddleStatus($userId, $riddleId)
            ?? 'non_commencee');
        $state = (new RiddleParticipationPolicyService())->decide(
            strtolower(remove_accents($status)),
            current_user_can('manage_options'),
            get_post_status($riddleId) === 'draft',
            self::isAssociatedOrganizer($userId, $huntId),
            $huntId > 0 && get_field('chasse_cache_statut', $huntId) === 'termine',
            $huntId > 0 && CoreServiceFactory::huntEngagement($wpdb)->isEngaged($userId, $huntId),
            CoreServiceFactory::riddleEngagement($wpdb)->isEngaged($userId, $riddleId),
            self::prerequisitesMet($userId, $riddleId)
        );
        return !$state['rediriger'];
    }

    private static function isAssociatedOrganizer(int $userId, int $huntId): bool
    {
        if ($userId <= 0 || $huntId <= 0) {
            return false;
        }
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        $userIds = $organizerId === null
            ? []
            : $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));
        return in_array($userId, $userIds, true);
    }

}
