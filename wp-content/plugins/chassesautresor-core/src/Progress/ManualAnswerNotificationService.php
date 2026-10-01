<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Notify the organizer when a player submits an answer for manual review.
 */
final class ManualAnswerNotificationService
{
    public function notify(int $userId, int $riddleId, string $answer, string $attemptUid): bool
    {
        $user = get_userdata($userId);
        if (!$user) {
            return false;
        }

        $recipient = $this->getOrganizerEmail($riddleId);
        if (!is_email($recipient)) {
            $recipient = (string) get_option('admin_email');
        }
        if (!is_email($recipient)) {
            return false;
        }

        $riddleTitle = html_entity_decode((string) get_the_title($riddleId), ENT_QUOTES, 'UTF-8');
        $profileUrl = get_author_posts_url($userId);
        $reviewUrl = add_query_arg(['uid' => $attemptUid], home_url('/traitement-tentative'));
        $message = '<p>' . sprintf(
            esc_html__('Une nouvelle réponse manuelle a été soumise par %s.', 'chassesautresor-com'),
            '<strong><a href="' . esc_url($profileUrl) . '">' . esc_html($user->user_login) . '</a></strong>'
        ) . '</p>';
        $message .= '<p><strong>' . esc_html__('Énigme :', 'chassesautresor-com') . '</strong> '
            . esc_html($riddleTitle) . '</p>';
        $message .= '<p><strong>' . esc_html__('Réponse :', 'chassesautresor-com') . '</strong><br>'
            . nl2br(esc_html($answer)) . '</p>';
        $message .= '<p><strong>' . esc_html__('Identifiant :', 'chassesautresor-com') . '</strong> '
            . esc_html($attemptUid) . '</p>';
        $message .= '<p><a href="' . esc_url($reviewUrl) . '">'
            . esc_html__('Traiter cette tentative', 'chassesautresor-com') . '</a></p>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $user->display_name . ' <' . $user->user_email . '>',
        ];

        return (bool) wp_mail(
            $recipient,
            sprintf(__('[Réponse Énigme] %s', 'chassesautresor-com'), $riddleTitle),
            $message,
            $headers
        );
    }

    private function getOrganizerEmail(int $riddleId): string
    {
        $huntId = $this->normalizeId(get_field('enigme_chasse_associee', $riddleId, false));
        $organizerId = $huntId > 0
            ? $this->normalizeId(get_field('chasse_cache_organisateur', $huntId))
            : 0;

        return $organizerId > 0 ? (string) get_field('email_organisateur', $organizerId) : '';
    }

    /** @param mixed $value */
    private function normalizeId($value): int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_object($value) && isset($value->ID) ? (int) $value->ID : (int) $value;
    }
}
