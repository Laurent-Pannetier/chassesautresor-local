<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Build the portable data model used to render a hunt status badge. */
final class HuntStatusBadgeService
{
    public function build(string $status, ?string $validation): array
    {
        $status = $status !== '' ? $status : 'revision';
        $normalized = $status === 'payante' ? 'en_cours' : $status;
        [$label, $icon] = $this->labelAndIcon($status, $validation);

        return [
            'statut' => $normalized,
            'label' => $label,
            'base_class' => 'statut-' . $normalized,
            'icon_name' => $icon,
            'icon_html' => '',
        ];
    }

    private function translate(string $text): string
    {
        return function_exists('__') ? __($text, 'chassesautresor-com') : $text;
    }

    private function labelAndIcon(string $status, ?string $validation): array
    {
        if ($status === 'revision') {
            return match ($validation) {
                'creation' => [$this->translate('création'), 'add'],
                'correction', 'edition' => [$this->translate('correction'), 'edition'],
                'en_attente' => [$this->translate('en attente'), 'pending'],
                default => [$this->translate('révision'), 'edition'],
            };
        }

        return match ($status) {
            'payante', 'en_cours' => [$this->translate('en cours'), 'in_progress'],
            'a_venir' => [$this->translate('à venir'), 'hourglass'],
            'termine' => [$this->translate('terminée'), 'finish'],
            'en_attente' => [$this->translate('en attente'), 'pending'],
            default => [$this->translate($status), null],
        };
    }
}
