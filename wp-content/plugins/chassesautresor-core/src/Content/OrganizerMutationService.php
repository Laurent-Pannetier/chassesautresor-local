<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Apply organizer field mutations independently from the AJAX transport.
 */
class OrganizerMutationService {
    private const FIELD_ALIASES = [
        'email_contact' => 'profil_public_email_contact',
        'parlez_de_vous_presentation' => 'description_longue',
        'logo_organisateur' => 'logo_organisateur',
    ];

    private const EDITABLE_FIELDS = [
        'post_title',
        'email_contact',
        'profil_public_email_contact',
        'parlez_de_vous_presentation',
        'description_longue',
        'logo_organisateur',
        'liens_publics',
        'coordonnees_bancaires',
    ];

    /**
     * @param mixed $value
     * @param callable(string): string $sanitizeText
     * @param callable(string): string $sanitizeUrl
     * @param callable(string): string $stripTags
     * @param callable(array<string, mixed>): mixed $updatePost
     * @param callable(mixed): bool $isError
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(string, int): mixed $getField
     * @param callable(int, string): mixed $getMeta
     * @return array{error: string|null, field: string, value: mixed}
     */
    public function apply(
        int $organizerId,
        string $field,
        $value,
        callable $sanitizeText,
        callable $sanitizeUrl,
        callable $stripTags,
        callable $updatePost,
        callable $isError,
        callable $updateField,
        callable $getField,
        callable $getMeta
    ): array {
        if (!in_array($field, self::EDITABLE_FIELDS, true)) {
            return $this->result($field, $value, 'field_not_allowed');
        }

        $targetField = self::FIELD_ALIASES[$field] ?? $field;
        if ($targetField === 'logo_organisateur') {
            $value = abs((int) $value);
        }

        if ($targetField === 'description_longue') {
            $plainText = trim($stripTags((string) $value));
            if (mb_strlen($plainText) < 50) {
                return $this->result($field, $value, 'description_too_short');
            }
        }

        if ($field === 'post_title') {
            $updated = $updatePost(['ID' => $organizerId, 'post_title' => $value]);
            return $this->result($field, $value, $isError($updated) ? 'title_update_failed' : null);
        }

        if ($field === 'liens_publics') {
            return $this->updatePublicLinks(
                $organizerId,
                (string) $value,
                $sanitizeText,
                $sanitizeUrl,
                $updateField,
                $getField
            );
        }

        if ($field === 'coordonnees_bancaires') {
            return $this->updateBankDetails(
                $organizerId,
                (string) $value,
                $sanitizeText,
                $updateField,
                $getField
            );
        }

        $storedValue = is_numeric($value) ? (int) $value : $value;
        $updated = $updateField($targetField, $storedValue, $organizerId);
        $savedValue = $getMeta($organizerId, $targetField);
        $equivalent = trim((string) $savedValue) === trim(stripslashes((string) $value));

        return $this->result($field, $value, $updated || $equivalent ? null : 'field_update_failed');
    }

    /** @return array{error: string|null, field: string, value: mixed} */
    private function updatePublicLinks(
        int $organizerId,
        string $value,
        callable $sanitizeText,
        callable $sanitizeUrl,
        callable $updateField,
        callable $getField
    ): array {
        $rows = json_decode(stripslashes($value), true);
        if (!is_array($rows)) {
            return $this->result('liens_publics', $value, 'invalid_format');
        }

        $links = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = $sanitizeText((string) ($row['type_de_lien'] ?? ''));
            $url = $sanitizeUrl((string) ($row['url_lien'] ?? ''));
            if ($type !== '' && $url !== '') {
                $links[] = ['type_de_lien' => $type, 'url_lien' => $url];
            }
        }

        $updated = $updateField('liens_publics', $links, $organizerId);
        $saved = $getField('liens_publics', $organizerId);
        $saved = is_array($saved) ? array_values($saved) : [];
        $equivalent = json_encode($saved) === json_encode($links);

        return $this->result('liens_publics', $links, $updated || $equivalent ? null : 'links_update_failed');
    }

    /** @return array{error: string|null, field: string, value: mixed} */
    private function updateBankDetails(
        int $organizerId,
        string $value,
        callable $sanitizeText,
        callable $updateField,
        callable $getField
    ): array {
        $data = json_decode(stripslashes($value), true);
        $data = is_array($data) ? $data : [];
        $iban = $sanitizeText((string) ($data['iban'] ?? ''));
        $bic = $sanitizeText((string) ($data['bic'] ?? ''));
        $ibanUpdated = $updateField('iban', $iban, $organizerId);
        $bicUpdated = $updateField('bic', $bic, $organizerId);
        $updateField('gagnez_de_largent_iban', $iban, $organizerId);
        $updateField('gagnez_de_largent_bic', $bic, $organizerId);
        $equivalent = $getField('iban', $organizerId) === $iban
            && $getField('bic', $organizerId) === $bic;
        $error = (($ibanUpdated !== false && $bicUpdated !== false) || $equivalent)
            ? null
            : 'bank_details_update_failed';

        return $this->result('coordonnees_bancaires', ['iban' => $iban, 'bic' => $bic], $error);
    }

    /**
     * @param mixed $value
     * @return array{error: string|null, field: string, value: mixed}
     */
    private function result(string $field, $value, ?string $error): array {
        return ['error' => $error, 'field' => $field, 'value' => $value];
    }
}
