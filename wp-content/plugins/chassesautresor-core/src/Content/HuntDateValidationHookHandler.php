<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate hunt dates submitted through native ACF forms.
 */
final class HuntDateValidationHookHandler
{
    private const CHARACTERISTICS_FIELD = 'field_67ca7fd7f5117';
    private const START_DATE_FIELD = 'field_67b58c6fd98ec';
    private const START_MODE_FIELD = 'field_67ca858935c21';

    public static function register(callable $addFilter): void
    {
        $addFilter('acf/validate_value/name=date_de_fin', [self::class, 'validate'], 10, 4);
    }

    public static function validate($valid, $value, $field, $input)
    {
        if ($valid !== true) {
            return $valid;
        }

        $postId = isset($_POST['post_ID']) ? (int) $_POST['post_ID'] : 0;
        if (\get_post_type($postId) !== 'chasse') {
            return $valid;
        }

        $endDate = self::normalizeDate((string) $value);
        $characteristics = $_POST['acf'][self::CHARACTERISTICS_FIELD] ?? [];
        if (!is_array($characteristics)) {
            return $valid;
        }

        $today = \wp_date('Y-m-d', (int) \current_time('timestamp'));
        $error = self::validateDates(
            (string) ($characteristics[self::START_DATE_FIELD] ?? ''),
            $endDate,
            (string) ($characteristics[self::START_MODE_FIELD] ?? ''),
            $today
        );

        if ($error === 'before_start') {
            return \__(
                '⚠️ Erreur : La date de fin ne peut pas être antérieure à la date de début.',
                'chassesautresor-com'
            );
        }

        if ($error === 'before_today') {
            return \__(
                '⚠️ Erreur : La date de fin ne peut pas être antérieure à la date du jour '
                    . 'si la chasse commence maintenant.',
                'chassesautresor-com'
            );
        }

        return $valid;
    }

    public static function validateDates(
        string $startDate,
        string $endDate,
        string $startMode,
        string $today
    ): ?string {
        if ($startDate !== '' && $endDate !== '' && strtotime($endDate) < strtotime($startDate)) {
            return 'before_start';
        }

        if ($startMode === 'maintenant' && $endDate !== '' && strtotime($endDate) < strtotime($today)) {
            return 'before_today';
        }

        return null;
    }

    private static function normalizeDate(string $date): string
    {
        if (preg_match('/^\d{8}$/', $date) !== 1) {
            return $date;
        }

        return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
    }
}
