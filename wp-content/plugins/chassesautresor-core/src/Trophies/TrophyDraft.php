<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Fields an administrator can set from the trophy wall. */
final class TrophyDraft
{
    private const TITLE_LENGTH = 190;
    private const DESCRIPTION_LENGTH = 300;
    private const KEY_LENGTH = 120;

    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly int $huntId,
        public readonly string $scope,
        public readonly string $eventKey,
        public readonly int $imageId
    ) {
    }

    /** @param array<string, mixed> $input */
    public static function from(array $input, int $imageId = 0): ?self
    {
        $title = self::clip(self::plain((string) ($input['title'] ?? '')), self::TITLE_LENGTH);
        $description = self::clip(trim(strip_tags((string) ($input['description'] ?? ''))), self::DESCRIPTION_LENGTH);
        $eventKey = self::clip(trim((string) ($input['event_key'] ?? '')), self::KEY_LENGTH);
        $scope = (string) ($input['scope'] ?? 'user');
        if (!in_array($scope, ['user', 'team', 'both'], true)) {
            $scope = 'user';
        }
        $huntId = max(0, (int) ($input['hunt_id'] ?? 0));
        if ($title === '' || preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,119}$/', $eventKey) !== 1) {
            return null;
        }

        return new self($title, $description, $huntId, $scope, $eventKey, max(0, $imageId));
    }

    private static function plain(string $value): string
    {
        $value = trim(strip_tags($value));
        $collapsed = preg_replace('/\s+/u', ' ', $value);

        return trim(is_string($collapsed) ? $collapsed : $value);
    }

    private static function clip(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
