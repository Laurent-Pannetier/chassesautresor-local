<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ShellEditionSaveWiringTest extends TestCase {
    public function testDashboardEditionQueuesSimpleFields(): void {
        $root = dirname(__DIR__);
        $queue = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/assets/js/shell-edition-save.js'
        );
        $fields = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/assets/js/core/champ-init.js'
        );
        $hunt = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/assets/js/chasse-edit.js'
        );
        $riddleHandler = (string) file_get_contents(
            $root . '/wp-content/plugins/chassesautresor-core/src/Content/RiddleFieldMutationAjaxHandler.php'
        );
        $huntHandler = (string) file_get_contents(
            $root . '/wp-content/plugins/chassesautresor-core/src/Content/HuntFieldMutationAjaxHandler.php'
        );

        $this->assertStringContainsString('ctaShellEditionSave?.shouldQueue', $fields);
        $this->assertStringContainsString("return Promise.resolve('queued')", $fields);
        $this->assertStringContainsString('enregistrer_champs_enigme', $queue);
        $this->assertStringContainsString('enregistrer_champs_chasse', $queue);
        $this->assertStringContainsString('beforeunload', $queue);
        $this->assertStringContainsString('champs_caches.chasse_cache_gagnants', $queue);
        $this->assertStringContainsString('champs_caches.chasse_cache_date_decouverte', $queue);
        $this->assertStringContainsString('queueDates', $hunt);
        $this->assertStringContainsString('wp_ajax_enregistrer_champs_enigme', $riddleHandler);
        $this->assertStringContainsString('wp_ajax_enregistrer_champs_chasse', $huntHandler);
        $template = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/template-parts/enigme/enigme-edition-main.php'
        );
        $mutation = (string) file_get_contents(
            $root . '/wp-content/plugins/chassesautresor-core/src/Content/RiddleMutationService.php'
        );
        $this->assertStringContainsString('champ-texte-editeur', $template);
        $this->assertStringContainsString('champ-images-editeur', $template);
        $huntTemplate = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/template-parts/chasse/chasse-edition-main.php'
        );
        $this->assertStringContainsString('champ-recompense-editeur', $huntTemplate);
        $this->assertStringContainsString('chasse_principale_description', $huntTemplate);
        $this->assertStringNotContainsString('chasse-edition-description', $huntTemplate);
        $this->assertStringContainsString("closest('.edition-row')", $queue);
        $this->assertStringContainsString("new MouseEvent('mousedown'", $queue);
        $this->assertStringContainsString('enigme_visuel_image', $queue);
        $this->assertStringContainsString('enigme_visuel_texte', $mutation);
        $this->assertStringContainsString('wp_kses_post', $mutation);
        $this->assertStringContainsString('enigme_visuel_image', $mutation);
        $this->assertStringContainsString('protectAfterSave', $riddleHandler);
        $this->assertStringNotContainsString('sleep(', $this->batchMethod($huntHandler));
        $this->assertStringContainsString('Édition énigme', $template);
        $this->assertStringNotContainsString('panneau-fermer', $template);
        $this->assertStringContainsString('bouton-supprimer-enigme', $template);
        $this->assertStringContainsString('Supprimer l’énigme', $template);
        $this->assertStringNotContainsString('Suppression énigme', $template);
        $tentatives = strpos($template, 'id="enigme-tab-soumission"');
        $deleteButton = strpos($template, 'bouton-supprimer-enigme');
        $this->assertNotFalse($tentatives);
        $this->assertNotFalse($deleteButton);
        $this->assertGreaterThan($tentatives, $deleteButton);
        $this->assertStringContainsString('Édition chasse', $huntTemplate);
        $this->assertStringNotContainsString('panneau-fermer', $huntTemplate);
        $this->assertStringContainsString('shell-edition-save__cancel', $queue);
        $this->assertStringContainsString('function restore()', $queue);
        $this->assertStringContainsString('registerExtra', $queue);
        $steps = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/assets/js/riddle-steps-edit.js'
        );
        $answer = (string) file_get_contents(
            $root . '/wp-content/themes/chassesautresor/assets/js/enigme-final-answer-edit.js'
        );
        $this->assertStringContainsString("setExtra('etape'", $steps);
        $this->assertStringContainsString('supprimer_etape_enigme', $steps);
        $this->assertStringContainsString('reordonner_etapes_enigme', $steps);
        $this->assertStringContainsString("setExtra('reponse-finale'", $answer);
        $this->assertStringContainsString('enregistrer_reponse_finale_enigme', $answer);
    }

    private function batchMethod(string $source): string {
        $start = strpos($source, 'function handleBatch');
        $end = strpos($source, 'function applyField');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $this->assertGreaterThan($start, $end);

        return substr($source, (int) $start, (int) $end - (int) $start);
    }
}
