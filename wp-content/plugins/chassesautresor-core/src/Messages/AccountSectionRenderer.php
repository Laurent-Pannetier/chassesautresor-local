<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/** Dispatch account section templates to plugin-owned renderers. */
final class AccountSectionRenderer {
    public function render(string $template): string {
        if ($template === 'content-statistiques.php') {
            return (new AccountStatisticsRenderer())->render((int) get_current_user_id());
        }
        if ($template === 'content-outils.php') {
            return (new AccountToolsRenderer())->render();
        }
        if ($template === 'content-organisateurs.php') {
            $page = isset($_GET['page']) ? absint(wp_unslash($_GET['page'])) : 1;

            return (new AccountOrganizersRenderer())->render($page);
        }

        return '';
    }
}
