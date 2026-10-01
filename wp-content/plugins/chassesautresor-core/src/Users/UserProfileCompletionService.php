<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Users;

/** Evaluate the account fields required before a user can start organizer workflows. */
final class UserProfileCompletionService {
    /** @return array{complete:bool,missing:array<int,string>} */
    public function evaluate(int $userId): array {
        $user = get_userdata($userId);
        if (!$user) {
            return [
                'complete' => false,
                'missing' => [__('Profil utilisateur introuvable', 'chassesautresor-com')],
            ];
        }

        $fields = apply_filters('cat_required_user_profile_fields', $this->defaultFields(), $userId, $user);
        $missing = [];
        foreach ((array) $fields as $fieldKey => $configuration) {
            $configuration = is_string($configuration)
                ? ['label' => $configuration, 'source' => 'meta']
                : (array) $configuration;
            if (empty($configuration['label'])) {
                continue;
            }

            $value = $this->value($userId, $user, (string) $fieldKey, $configuration);
            if ($this->normalize($value) === '') {
                $missing[] = (string) $configuration['label'];
            }
        }

        return ['complete' => $missing === [], 'missing' => $missing];
    }

    /** @param array<int,string> $missingFields */
    public function missingFieldsMessage(array $missingFields): string {
        if ($missingFields === []) {
            return __('Veuillez compléter votre profil utilisateur.', 'chassesautresor-com');
        }

        return sprintf(
            /* translators: %s: comma-separated list of missing profile fields. */
            __('Veuillez compléter votre profil utilisateur : %s.', 'chassesautresor-com'),
            wp_sprintf_l('%l', $missingFields)
        );
    }

    /** @return array<string,array{label:string,source:string}> */
    private function defaultFields(): array {
        return [
            'first_name' => ['label' => __('Prénom', 'chassesautresor-com'), 'source' => 'meta'],
            'last_name' => ['label' => __('Nom', 'chassesautresor-com'), 'source' => 'meta'],
            'display_name' => ['label' => __('Nom d’affichage', 'chassesautresor-com'), 'source' => 'property'],
            'user_email' => ['label' => __('Adresse e-mail', 'chassesautresor-com'), 'source' => 'property'],
        ];
    }

    /** @param object $user
     *  @param array<string,mixed> $configuration
     *  @return mixed
     */
    private function value(int $userId, $user, string $fieldKey, array $configuration) {
        if (!empty($configuration['callback']) && is_callable($configuration['callback'])) {
            return call_user_func($configuration['callback'], $userId, $user, $fieldKey, $configuration);
        }
        if (($configuration['source'] ?? 'meta') === 'property') {
            return $user->{$fieldKey} ?? '';
        }

        return get_user_meta($userId, $fieldKey, true);
    }

    /** @param mixed $value */
    private function normalize($value): string {
        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }
        if (is_array($value)) {
            return implode('', array_map('trim', array_map('strval', $value)));
        }

        return '';
    }
}
