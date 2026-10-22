<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** Render the authenticated user's notification center. */
final class NotificationsPageRenderer
{
    /** @param array<int, array<string, mixed>> $notifications */
    public function render(array $notifications): string
    {
        ob_start();
        echo '<div class="notifications-center" data-notifications-center>';
        echo '<div class="notifications-center__header">';
        echo '<p>' . esc_html__(
            'Retrouvez ici les trophées, indices et messages importants reçus sur le site.',
            'chassesautresor-com'
        ) . '</p>';
        if ($this->hasUnread($notifications)) {
            echo '<button type="button" class="bouton-texte secondaire" data-notifications-read-all>';
            echo esc_html__('Tout marquer comme lu', 'chassesautresor-com');
            echo '</button>';
        }
        echo '</div>';

        if ($notifications === []) {
            echo '<p class="notifications-center__empty">';
            echo esc_html__('Vous n’avez aucune notification pour le moment.', 'chassesautresor-com');
            echo '</p></div>';

            return trim((string) ob_get_clean());
        }

        echo '<ol class="notifications-list">';
        foreach ($notifications as $notification) {
            $this->item($notification);
        }
        echo '</ol></div>';

        return trim((string) ob_get_clean());
    }

    /** @param array<string, mixed> $notification */
    private function item(array $notification): void
    {
        $id = (int) ($notification['id'] ?? 0);
        $unread = empty($notification['read_at']);
        $sourceType = (string) ($notification['source_type'] ?? 'system');
        $class = 'notifications-list__item' . ($unread ? ' is-unread' : '');
        $date = $this->date((string) ($notification['created_at'] ?? ''));

        echo '<li class="' . esc_attr($class) . '" data-notification-id="' . esc_attr((string) $id) . '">';
        echo '<div class="notifications-list__icon" aria-hidden="true">' . $this->icon($sourceType) . '</div>';
        echo '<div class="notifications-list__content">';
        echo '<div class="notifications-list__meta">';
        echo '<span class="notifications-list__type">' . esc_html($this->typeLabel($sourceType)) . '</span>';
        if ($date !== '') {
            echo '<time datetime="' . esc_attr((string) ($notification['created_at'] ?? '')) . '">';
            echo esc_html($date) . '</time>';
        }
        echo '</div>';
        echo '<h2>' . esc_html((string) ($notification['title'] ?? '')) . '</h2>';
        $body = trim((string) ($notification['body'] ?? ''));
        if ($body !== '') {
            echo '<p>' . esc_html($body) . '</p>';
        }
        echo '</div>';
        if ($unread) {
            echo '<button type="button" class="notifications-list__read" data-notification-read>';
            echo esc_html__('Marquer comme lu', 'chassesautresor-com');
            echo '</button>';
        }
        echo '</li>';
    }

    /** @param array<int, array<string, mixed>> $notifications */
    private function hasUnread(array $notifications): bool
    {
        foreach ($notifications as $notification) {
            if (empty($notification['read_at'])) {
                return true;
            }
        }

        return false;
    }

    private function typeLabel(string $sourceType): string
    {
        return match ($sourceType) {
            'trophy' => __('Trophée', 'chassesautresor-com'),
            'hint' => __('Indice', 'chassesautresor-com'),
            default => __('Message', 'chassesautresor-com'),
        };
    }

    private function icon(string $sourceType): string
    {
        return match ($sourceType) {
            'trophy' => '🏆',
            'hint' => '💡',
            default => '🔔',
        };
    }

    private function date(string $date): string
    {
        if ($date === '') {
            return '';
        }

        return function_exists('mysql2date')
            ? (string) mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $date)
            : $date;
    }
}
