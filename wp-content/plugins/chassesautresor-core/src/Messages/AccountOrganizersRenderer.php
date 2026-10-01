<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use Closure;

/** Render the organizer moderation section of the administrator account area. */
final class AccountOrganizersRenderer {
    private Closure $provider;
    private Closure $tableRenderer;

    public function __construct(?callable $provider = null, ?callable $tableRenderer = null) {
        $this->provider = Closure::fromCallable(
            $provider ?? static fn (): array => \recuperer_organisateurs_pending()
        );
        $this->tableRenderer = Closure::fromCallable(
            $tableRenderer ?? static function (array $organizers, int $page): string {
                ob_start();
                \afficher_tableau_organisateurs_pending($organizers, $page);

                return (string) ob_get_clean();
            }
        );
    }

    public function render(int $page): string {
        $organizers = (array) ($this->provider)();
        $states = [];
        foreach ($organizers as $entry) {
            if (!empty($entry['statut'])) {
                $states[(string) $entry['statut']] = true;
            }
        }

        ob_start();
        ?>
        <section>
            <h2><?= esc_html__('Organisateurs', 'chassesautresor-com'); ?></h2>
            <?php if ($organizers === []) : ?>
                <p><?= esc_html__('Aucun organisateur.', 'chassesautresor-com'); ?></p>
            <?php else : ?>
                <div class="stats-header">
                    <span class="etiquette"><?= esc_html(sprintf(
                        _n(
                            '%d organisateur',
                            '%d organisateurs',
                            count($organizers),
                            'chassesautresor-com'
                        ),
                        count($organizers)
                    )); ?></span>
                    <div class="stats-filtres">
                        <label for="filtre-etat">
                            <?= esc_html__('Filtrer par état :', 'chassesautresor-com'); ?>
                        </label>
                        <select id="filtre-etat">
                            <option value="tous"><?= esc_html__('Tous', 'chassesautresor-com'); ?></option>
                            <?php foreach (array_keys($states) as $state) : ?>
                                <option value="<?= esc_attr($state); ?>"><?= esc_html($state); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?= ($this->tableRenderer)($organizers, max(1, $page)); ?>
            <?php endif; ?>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }
}
