<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/** Evaluate and renew the retry delay shared by all riddle submissions. */
final class RiddleRetryPolicyService
{
    private RiddleRetryRepository $repository;
    private RiddleRetryConfiguration $configuration;
    private Closure $clock;

    public function __construct(
        RiddleRetryRepository $repository,
        RiddleRetryConfiguration $configuration,
        ?callable $clock = null
    ) {
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->clock = Closure::fromCallable($clock ?? [self::class, 'now']);
    }

    /** @return array{blocked:bool,retry_at:?string,server_now:string,remaining_seconds:int,message:string} */
    public function getState(int $userId, int $riddleId): array
    {
        $now = $this->currentTime();
        $delay = $this->configuration->getDelaySeconds($riddleId);
        $row = $userId > 0 && $riddleId > 0 && $delay > 0
            ? $this->repository->find($userId, $riddleId)
            : null;
        $retryAt = $this->parseDatabaseDate($row->retry_at_utc ?? null);
        $remaining = $retryAt === null ? 0 : max(0, $retryAt->getTimestamp() - $now->getTimestamp());
        $blocked = $remaining > 0;

        return [
            'blocked' => $blocked,
            'retry_at' => $blocked ? $retryAt->format('Y-m-d\TH:i:s\Z') : null,
            'server_now' => $now->format('Y-m-d\TH:i:s\Z'),
            'remaining_seconds' => $remaining,
            'message' => $blocked
                ? __('Vous pourrez proposer une nouvelle réponse à l’expiration du délai.', 'chassesautresor-com')
                : '',
        ];
    }

    /** Renew only after the caller has persisted a rejected attempt in its transaction. */
    public function renewAfterFailure(int $userId, int $riddleId, string $result, string $attemptUid): bool
    {
        $delay = $this->configuration->getDelaySeconds($riddleId);
        if ($result !== 'faux' || $userId <= 0 || $riddleId <= 0 || $delay <= 0 || $attemptUid === '') {
            return true;
        }

        $now = $this->currentTime();

        return $this->repository->save(
            $userId,
            $riddleId,
            $now->modify('+' . $delay . ' seconds')->format('Y-m-d H:i:s'),
            $attemptUid,
            $now->format('Y-m-d H:i:s')
        );
    }

    public function clearForRiddle(int $riddleId): int
    {
        return $riddleId > 0 ? $this->repository->deleteForRiddle($riddleId) : 0;
    }

    private function currentTime(): DateTimeImmutable
    {
        $value = ($this->clock)();

        return DateTimeImmutable::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'));
    }

    private function parseDatabaseDate($value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    private static function now(): DateTimeInterface
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
