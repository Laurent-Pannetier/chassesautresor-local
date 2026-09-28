<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/**
 * Handle persistence rules for site-wide messages.
 */
class SiteMessageService
{
    private UserMessageRepository $repository;

    public function __construct(UserMessageRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Store a persistent site message.
     *
     * @param array<string, mixed> $message
     */
    public function store(array $message, ?string $locale = null, ?int $expires = null): bool
    {
        $expiresAt = $this->resolveExpiration($expires);
        $inserted = $this->repository->insert(
            0,
            wp_json_encode($message),
            'site',
            $expiresAt,
            $locale
        );

        return $inserted !== 0;
    }

    /**
     * Remove persistent site messages matching a translation key.
     */
    public function removeByKey(string $key): void
    {
        foreach ($this->repository->get(0, 'site', null) as $row) {
            $message = $this->decodeRow($row);

            if (($message['message_key'] ?? '') === $key) {
                $this->repository->delete((int) $row['id']);
            }
        }
    }

    /**
     * Return active persistent site messages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActive(): array
    {
        $messages = [];

        foreach ($this->repository->get(0, 'site', false) as $row) {
            $message = $this->decodeRow($row);

            if ($message !== []) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * Keep the first occurrence of each keyed message.
     *
     * @param array<int, array<string, mixed>> $messages
     * @return array<int, array<string, mixed>>
     */
    public function deduplicate(array $messages): array
    {
        $unique = [];
        $seenKeys = [];

        foreach ($messages as $message) {
            $key = $message['message_key'] ?? null;

            if ($key !== null) {
                if (isset($seenKeys[$key])) {
                    continue;
                }

                $seenKeys[$key] = true;
            }

            $unique[] = $message;
        }

        return $unique;
    }

    /**
     * Remove messages created by an obsolete test migration.
     */
    public function removeLegacyTestMessages(): void
    {
        foreach ($this->repository->get(0, 'site', null) as $row) {
            $message = $this->decodeRow($row);

            if (($message['content'] ?? '') === 'prout') {
                $this->repository->delete((int) $row['id']);
            }
        }
    }

    private function resolveExpiration(?int $expires): ?string
    {
        if ($expires === null) {
            return null;
        }

        $now = (int) current_time('timestamp');
        $timestamp = $expires > $now ? $expires : $now + $expires;

        return gmdate('c', $timestamp);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decodeRow(array $row): array
    {
        $message = json_decode((string) ($row['message'] ?? ''), true);

        if (!is_array($message)) {
            return [];
        }

        if (!empty($row['locale'])) {
            $message['locale'] = $row['locale'];
        }

        return $message;
    }
}
