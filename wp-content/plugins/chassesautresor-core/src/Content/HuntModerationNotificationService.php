<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Messages\AccountMessageService;
use Closure;

final class HuntModerationNotificationService
{
    private AccountMessageService $messages;
    private Closure $sendEmail;
    private Closure $recipientResolver;

    public function __construct(
        AccountMessageService $messages,
        ?callable $sendEmail = null,
        ?callable $recipientResolver = null
    ) {
        $this->messages = $messages;
        $this->sendEmail = Closure::fromCallable($sendEmail ?? 'wp_mail');
        $this->recipientResolver = Closure::fromCallable($recipientResolver ?? [$this, 'resolveRecipients']);
    }

    /** @param int[] $userIds */
    public function notify(
        string $action,
        int $organizerId,
        int $huntId,
        array $userIds,
        string $huntTitle,
        string $huntUrl,
        string $administratorMessage = ''
    ): void {
        if ($action === 'correction') {
            $this->addCorrectionMessages($huntId, $userIds, $huntTitle, $huntUrl, $administratorMessage);
        } else {
            $this->addFlashMessages($action, $userIds, $huntTitle);
        }

        $this->sendEmail($action, $organizerId, $huntTitle, $huntUrl, $administratorMessage);
    }

    /** @param int[] $userIds */
    private function addFlashMessages(string $action, array $userIds, string $huntTitle): void
    {
        $formats = [
            'valider' => __(
                'Votre demande de validation pour la chasse « %s » a été acceptée.',
                'chassesautresor-com'
            ),
            'bannir' => __('Votre chasse « %s » a été bannie.', 'chassesautresor-com'),
            'supprimer' => __('Votre chasse « %s » a été supprimée.', 'chassesautresor-com'),
        ];
        if (!isset($formats[$action])) {
            return;
        }

        $type = $action === 'valider' ? 'success' : 'error';
        foreach ($userIds as $userId) {
            $this->messages->addFlash((int) $userId, [
                'text' => sprintf($formats[$action], esc_html($huntTitle)),
                'type' => $type,
                'dismissible' => false,
            ]);
        }
    }

    /** @param int[] $userIds */
    private function addCorrectionMessages(
        int $huntId,
        array $userIds,
        string $huntTitle,
        string $huntUrl,
        string $administratorMessage
    ): void {
        $text = sprintf(
            __('Votre demande de validation pour la chasse « %s » nécessite des corrections.', 'chassesautresor-com'),
            '<a href="' . esc_url($huntUrl) . '">' . esc_html($huntTitle) . '</a>'
        );
        if ($administratorMessage !== '') {
            $text .= '<br>' . sprintf(
                __('Message de l’administrateur : %s', 'chassesautresor-com'),
                nl2br(esc_html($administratorMessage))
            );
        }
        $text .= '<br>' . __('Une copie de ce message vous a été envoyée par email.', 'chassesautresor-com');

        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            $this->messages->addPersistent($userId, 'correction_chasse_' . $huntId, [
                'text' => $text,
                'type' => 'warning',
                'dismissible' => true,
                'chasse_scope' => $huntId,
                'include_enigmes' => true,
            ]);
            $this->messages->addPersistent($userId, 'correction_info_chasse_' . $huntId, [
                'text' => sprintf(
                    __('Votre chasse est éligible à une %1$sdemande de validation%2$s.', 'chassesautresor-com'),
                    '<a href="' . esc_url($huntUrl . '#cta-validation-chasse') . '">',
                    '</a>'
                ),
                'type' => 'info',
                'dismissible' => false,
                'chasse_scope' => $huntId,
                'include_enigmes' => true,
            ]);
        }
    }

    private function sendEmail(
        string $action,
        int $organizerId,
        string $huntTitle,
        string $huntUrl,
        string $administratorMessage
    ): void {
        $subjects = [
            'valider' => __('Votre chasse est maintenant validée !', 'chassesautresor-com'),
            'correction' => __('Corrections requises pour votre chasse', 'chassesautresor-com'),
            'bannir' => __('Chasse bannie', 'chassesautresor-com'),
            'supprimer' => __('Chasse supprimée', 'chassesautresor-com'),
        ];
        if (!isset($subjects[$action])) {
            return;
        }

        $recipients = self::normalizeRecipients(($this->recipientResolver)($organizerId));
        $body = sprintf(
            __('La chasse « %1$s » a fait l’objet de l’action suivante : %2$s.', 'chassesautresor-com'),
            $huntTitle,
            $subjects[$action]
        );
        if ($huntUrl !== '') {
            $body .= "\n" . $huntUrl;
        }
        if ($administratorMessage !== '') {
            $body .= "\n" . sprintf(
                __('Message de l’administrateur : %s', 'chassesautresor-com'),
                $administratorMessage
            );
        }

        ($this->sendEmail)(
            $recipients,
            $subjects[$action],
            $body,
            ['Bcc: ' . $this->adminEmail()]
        );
    }

    /** @param mixed $recipients @return string[] */
    private static function normalizeRecipients($recipients): array
    {
        return array_values(array_filter(array_map('strval', (array) $recipients)));
    }

    private function adminEmail(): string
    {
        $email = (string) get_option('admin_email');

        return function_exists('sanitize_email') ? sanitize_email($email) : $email;
    }

    /** @return string[] */
    private function resolveRecipients(int $organizerId): array
    {
        $emails = [];
        $publicEmail = get_field('email_organisateur', $organizerId);
        $publicEmail = is_array($publicEmail) ? reset($publicEmail) : $publicEmail;
        if (is_string($publicEmail) && is_email($publicEmail)) {
            $emails[] = sanitize_email($publicEmail);
        }

        foreach ((array) get_field('utilisateurs_associes', $organizerId) as $userValue) {
            $userId = is_object($userValue) ? (int) $userValue->ID : (int) $userValue;
            $user = $userId > 0 ? get_userdata($userId) : false;
            if ($user && is_email($user->user_email)) {
                $emails[] = sanitize_email($user->user_email);
            }
        }

        return array_values(array_unique($emails ?: [sanitize_email((string) get_option('admin_email'))]));
    }
}
