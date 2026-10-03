<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Notify a player after an organizer accepts or refuses a manual answer.
 */
final class AnswerResultNotificationService
{
    public function notify(int $userId, int $riddleId, string $result): bool
    {
        $user = get_userdata($userId);
        if (!$user || !is_email($user->user_email)) {
            return false;
        }

        $accepted = $result === 'bon';
        $title = (string) get_the_title($riddleId);
        $resultLabel = $accepted
            ? __('Réponse acceptée', 'chassesautresor-com')
            : __('Réponse refusée', 'chassesautresor-com');
        $message = '<p>' . ($accepted
            ? esc_html__('Félicitations ! Votre réponse est correcte.', 'chassesautresor-com')
            : esc_html__('Votre réponse est incorrecte.', 'chassesautresor-com')) . '</p>';
        $message .= '<p><a href="' . esc_url(get_permalink($riddleId)) . '">'
            . ($accepted
                ? esc_html__('Retour à l’énigme', 'chassesautresor-com')
                : esc_html__('Réessayer l’énigme', 'chassesautresor-com')) . '</a></p>';

        $replyTo = $this->getOrganizerEmail($riddleId);
        if (!is_email($replyTo)) {
            $replyTo = (string) get_option('admin_email');
        }

        return (bool) wp_mail(
            $user->user_email,
            sprintf(__('[Chasses au Trésor] %1$s — %2$s', 'chassesautresor-com'), $title, $resultLabel),
            $message,
            ['Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $replyTo]
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
