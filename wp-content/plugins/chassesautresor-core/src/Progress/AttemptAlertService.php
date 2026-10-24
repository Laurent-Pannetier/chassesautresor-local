<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Notifications\NotificationService;

/** Email and notification-center alerts for manual review and a first automatic solve. */
final class AttemptAlertService
{
    public function __construct(
        private ?NotificationService $notifications = null,
        private ?AttemptAlertContext $context = null,
        private mixed $mailer = null
    ) {
    }

    public function notifyManualSubmission(
        int $userId,
        int $riddleId,
        string $answer,
        string $attemptUid
    ): bool {
        $context = $this->context();
        if ($context->isDemoMode() || $attemptUid === '') {
            return false;
        }

        $player = $context->player($userId);
        if ($player === null) {
            return false;
        }

        $title = $context->riddleTitle($riddleId);
        $reviewUrl = $context->reviewUrl($riddleId, $attemptUid);
        $eventKey = 'manual-attempt:' . $attemptUid;
        $sent = !$this->notifications()->hasEvent($eventKey) && $this->sendOrganizerMail(
            $context,
            $riddleId,
            $this->manualSubmissionMessage($player, $userId, $title, $answer, $attemptUid, $reviewUrl),
            sprintf(__('[Réponse Énigme] %s', 'chassesautresor-com'), $title),
            [
                'Content-Type: text/html; charset=UTF-8',
                'Reply-To: ' . $player['name'] . ' <' . $player['email'] . '>',
            ],
            true
        );
        $notificationId = $this->notifyStaff(
            $riddleId,
            'tentative_manuelle',
            'tentative',
            __('Réponse à valider', 'chassesautresor-com'),
            sprintf(
                __(
                    '%1$s a soumis une réponse pour « %2$s ». Retrouvez-la dans Outils, onglet Tentatives.',
                    'chassesautresor-com'
                ),
                $player['login'],
                $title
            ),
            80,
            $eventKey
        );

        return $sent || $notificationId > 0;
    }

    public function notifyManualDecision(int $userId, int $riddleId, string $result, string $attemptUid): int
    {
        if ($userId <= 0 || $attemptUid === '' || !in_array($result, ['bon', 'faux'], true)) {
            return 0;
        }

        $approved = $result === 'bon';
        $title = $this->context()->riddleTitle($riddleId);
        $heading = $approved
            ? __('Réponse validée', 'chassesautresor-com')
            : __('Réponse invalidée', 'chassesautresor-com');
        $template = $approved
            ? __(
                'Votre demande pour « %s » a été validée. Retrouvez-la dans Mon compte, Tentatives.',
                'chassesautresor-com'
            )
            : __(
                'Votre demande pour « %s » a été invalidée. Retrouvez-la dans Mon compte, Tentatives.',
                'chassesautresor-com'
            );

        return $this->notifications()->notify(
            'tentative_decision',
            'tentative',
            $riddleId,
            $heading,
            sprintf($template, $title),
            70,
            NotificationService::CHANNEL_CENTER,
            'manual-attempt-result:' . $attemptUid,
            [$userId]
        );
    }

    public function notifyFirstAutomaticSolve(int $userId, int $riddleId): bool
    {
        $context = $this->context();
        if (
            $context->isDemoMode()
            || $context->validationMode($riddleId) !== 'automatique'
            || $context->playerIsStaff($userId, $riddleId)
            || $context->eligibleSolvedCount($riddleId) !== 1
        ) {
            return false;
        }

        $player = $context->player($userId);
        if ($player === null) {
            return false;
        }

        $title = $context->riddleTitle($riddleId);
        $eventKey = 'first-solve:' . $riddleId;
        $sent = !$this->notifications()->hasEvent($eventKey) && $this->sendOrganizerMail(
            $context,
            $riddleId,
            $this->firstSolveMessage($player, $title, $context->riddleUrl($riddleId)),
            sprintf(
                __('[Chasses au Trésor] Première résolution — %s', 'chassesautresor-com'),
                $title
            ),
            ['Content-Type: text/html; charset=UTF-8'],
            false
        );
        $notificationId = $this->notifyStaff(
            $riddleId,
            'premiere_resolution',
            'enigme',
            __('Première résolution', 'chassesautresor-com'),
            sprintf(
                __('%1$s est le premier joueur à résoudre « %2$s ».', 'chassesautresor-com'),
                $player['login'],
                $title
            ),
            60,
            $eventKey
        );

        return $sent || $notificationId > 0;
    }

    private function context(): AttemptAlertContext
    {
        return $this->context ??= new AttemptAlertContext();
    }

    private function notifications(): NotificationService
    {
        if ($this->notifications === null) {
            $this->notifications = function_exists('cat_notification_service')
                ? cat_notification_service()
                : new NotificationService(cat_notification_repository());
        }

        return $this->notifications;
    }

    /** @param array{login:string,email:string,name:string} $player */
    private function manualSubmissionMessage(
        array $player,
        int $userId,
        string $title,
        string $answer,
        string $attemptUid,
        string $reviewUrl
    ): string {
        $profileUrl = function_exists('get_author_posts_url') ? (string) get_author_posts_url($userId) : '';
        $message = '<p>' . sprintf(
            esc_html__(
                'Une nouvelle réponse manuelle a été soumise par %s.',
                'chassesautresor-com'
            ),
            '<strong><a href="' . esc_url($profileUrl) . '">' . esc_html($player['login']) . '</a></strong>'
        ) . '</p>';
        $message .= '<p><strong>' . esc_html__('Énigme :', 'chassesautresor-com') . '</strong> '
            . esc_html($title) . '</p>';
        $message .= '<p><strong>' . esc_html__('Réponse :', 'chassesautresor-com') . '</strong><br>'
            . nl2br(esc_html($answer)) . '</p>';
        $message .= '<p><strong>' . esc_html__('Identifiant :', 'chassesautresor-com') . '</strong> '
            . esc_html($attemptUid) . '</p>';
        $message .= '<p><a href="' . esc_url($reviewUrl) . '">'
            . esc_html__('Traiter cette tentative', 'chassesautresor-com') . '</a></p>';

        return $message;
    }

    /** @param array{login:string,email:string,name:string} $player */
    private function firstSolveMessage(array $player, string $title, string $url): string
    {
        $message = '<p>' . sprintf(
            esc_html__('%1$s est le premier joueur à résoudre « %2$s ».', 'chassesautresor-com'),
            '<strong>' . esc_html($player['login']) . '</strong>',
            esc_html($title)
        ) . '</p>';
        if ($url !== '') {
            $message .= '<p><a href="' . esc_url($url) . '">'
                . esc_html__('Voir l’énigme', 'chassesautresor-com') . '</a></p>';
        }

        return $message;
    }

    /** @param string[] $headers */
    private function sendOrganizerMail(
        AttemptAlertContext $context,
        int $riddleId,
        string $message,
        string $subject,
        array $headers,
        bool $fallbackToAdmin
    ): bool {
        $recipient = $context->organizerEmail($riddleId);
        if (!$context->isEmail($recipient) && $fallbackToAdmin) {
            $recipient = $context->adminEmail();
        }
        if (!$context->isEmail($recipient)) {
            return false;
        }

        $mailer = $this->mailer ?? 'wp_mail';

        return (bool) $mailer($recipient, $subject, $message, $headers);
    }

    /** @return int */
    private function notifyStaff(
        int $riddleId,
        string $type,
        string $sourceType,
        string $title,
        string $body,
        int $priority,
        string $eventKey
    ): int {
        return $this->notifications()->notify(
            $type,
            $sourceType,
            $riddleId,
            $title,
            $body,
            $priority,
            NotificationService::CHANNEL_CENTER,
            $eventKey,
            $this->context()->staffUserIds($riddleId)
        );
    }
}
