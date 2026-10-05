<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Sign and verify short-lived protected riddle image URLs.
 */
final class ProtectedRiddleImageSignedUrlService
{
    public const DEFAULT_TTL_SECONDS = 3600;

    public function __construct(private readonly int $ttlSeconds = self::DEFAULT_TTL_SECONDS)
    {
    }

    /** @return array{exp:int,uid:int,sig:string} */
    public function sign(int $imageId, string $size, int $userId, ?int $now = null): array
    {
        $exp = ($now ?? time()) + max(60, $this->ttlSeconds);
        return [
            'exp' => $exp,
            'uid' => max(0, $userId),
            'sig' => $this->signature($imageId, $size, max(0, $userId), $exp),
        ];
    }

    public function verify(
        int $imageId,
        string $size,
        int $userId,
        int $exp,
        string $signature,
        ?int $now = null
    ): bool {
        if ($signature === '' || $exp < ($now ?? time())) {
            return false;
        }

        $expected = $this->signature($imageId, $size, max(0, $userId), $exp);
        return hash_equals($expected, $signature);
    }

    /** @param array<string, scalar> $query */
    public function buildUrl(int $imageId, string $size, int $userId, string $baseUrl, ?int $now = null): string
    {
        $signed = $this->sign($imageId, $size, $userId, $now);
        return \add_query_arg([
            'id' => $imageId,
            'taille' => $size,
            'exp' => $signed['exp'],
            'uid' => $signed['uid'],
            'sig' => $signed['sig'],
        ], $baseUrl);
    }

    private function signature(int $imageId, string $size, int $userId, int $exp): string
    {
        $payload = $imageId . '|' . $size . '|' . $userId . '|' . $exp;
        return hash_hmac('sha256', $payload, $this->secret());
    }

    private function secret(): string
    {
        if (defined('CHASSES_IMAGE_SIGNING_SECRET')) {
            $configured = constant('CHASSES_IMAGE_SIGNING_SECRET');
            if (is_string($configured) && $configured !== '') {
                return $configured;
            }
        }

        return \wp_salt('auth');
    }
}
