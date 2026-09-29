<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/**
 * Handle persistence rules for account messages.
 */
class AccountMessageService
{
    private UserMessageRepository $repository;

    public function __construct(UserMessageRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function addPersistent(
        int $userId,
        string $key,
        array $payload,
        ?string $locale = null,
        ?int $expires = null
    ): int {
        $this->removePersistent($userId, $key);

        return $this->repository->insert(
            $userId,
            wp_json_encode(array_merge(['key' => $key], $payload)),
            'persistent',
            $this->resolveExpiration($expires),
            $locale
        );
    }

    public function removePersistent(int $userId, string $key): void
    {
        foreach ($this->repository->get($userId, 'persistent', null) as $row) {
            $message = $this->decodeRow($row);

            if (($message['key'] ?? '') === $key) {
                $this->repository->delete((int) $row['id']);
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPersistent(int $userId, string $key): ?array
    {
        $messages = $this->getPersistent($userId, null);

        return $messages[$key] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getPersistent(int $userId, ?bool $expired = false): array
    {
        $messages = [];

        foreach ($this->repository->get($userId, 'persistent', $expired) as $row) {
            $message = $this->decodeRow($row);

            if ($message === []) {
                continue;
            }

            $key = isset($message['key']) ? (string) $message['key'] : (string) $row['id'];
            $messages[$key] = $message;
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function addFlash(int $userId, array $payload): int
    {
        return $this->repository->insert(
            $userId,
            wp_json_encode($payload),
            'flash'
        );
    }

    /**
     * Return active flash messages and delete them from storage.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pullFlash(int $userId): array
    {
        $messages = [];

        foreach ($this->repository->get($userId, 'flash', false) as $row) {
            $message = $this->decodeRow($row);

            if ($message !== []) {
                $messages[] = $message;
            }

            $this->repository->delete((int) $row['id']);
        }

        return $messages;
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
