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
    public function sign(
        int $imageId,
        string $size,
        int $userId,
        ?int $now = null,
        string $format = ''
    ): array {
        $format = $this->normalizeFormat($format);
        $exp = $this->stableExpiry($now ?? time());
        return [
            'exp' => $exp,
            'uid' => max(0, $userId),
            'sig' => $this->signature($imageId, $size, max(0, $userId), $exp, $format),
        ];
    }

    public function verify(
        int $imageId,
        string $size,
        int $userId,
        int $exp,
        string $signature,
        ?int $now = null,
        string $format = ''
    ): bool {
        if ($signature === '' || $exp < ($now ?? time())) {
            return false;
        }

        $format = $this->normalizeFormat($format);
        $expected = $this->signature($imageId, $size, max(0, $userId), $exp, $format);
        return hash_equals($expected, $signature);
    }

    public function buildUrl(
        int $imageId,
        string $size,
        int $userId,
        string $baseUrl,
        ?int $now = null,
        string $format = ''
    ): string {
        $format = $this->normalizeFormat($format);
        $signed = $this->sign($imageId, $size, $userId, $now, $format);
        $args = [
            'id' => $imageId,
            'taille' => $size,
            'exp' => $signed['exp'],
            'uid' => $signed['uid'],
            'sig' => $signed['sig'],
        ];
        if ($format !== '') {
            $args['fmt'] = $format;
        }

        return \add_query_arg($args, $baseUrl);
    }

    /**
     * Align expiry on the TTL window so the same viewer receives the same URL
     * until that window ends. Near the boundary the next window is used, which
     * keeps at least half a TTL of validity.
     */
    private function stableExpiry(int $now): int
    {
        $window = max(60, $this->ttlSeconds);
        $boundary = intdiv($now, $window) * $window + $window;
        if ($boundary - $now < intdiv($window, 2)) {
            $boundary += $window;
        }

        return $boundary;
    }

    private function signature(int $imageId, string $size, int $userId, int $exp, string $format = ''): string
    {
        $payload = $imageId . '|' . $size . '|' . $userId . '|' . $exp;
        if ($format !== '') {
            $payload .= '|' . $format;
        }

        return hash_hmac('sha256', $payload, $this->secret());
    }

    private function normalizeFormat(string $format): string
    {
        return strtolower($format) === 'webp' ? 'webp' : '';
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
