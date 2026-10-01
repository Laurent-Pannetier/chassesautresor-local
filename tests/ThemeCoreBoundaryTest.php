<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ThemeCoreBoundaryTest extends TestCase
{
    private const THEME_PATH = __DIR__ . '/../wp-content/themes/chassesautresor';

    public function testThemeDoesNotLoadCoreImplementationFiles(): void
    {
        $violations = $this->findPhpMatches(
            '/plugins\/chassesautresor-core\/src|chassesautresor-core\/src/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotRegisterAjaxEndpoints(): void
    {
        $violations = $this->findPhpMatches(
            '/add_action\s*\(\s*[\'\"]wp_ajax_(?:nopriv_)?/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotConstructCoreRepositories(): void
    {
        $violations = $this->findPhpMatches(
            '/new\s+(?:\\?ChassesAuTresor\\Core\\[^;()]+\\)?[A-Za-z]+Repository\s*\(/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnPluginLifecycleOrBusinessMutationHooks(): void
    {
        $violations = $this->findPhpMatches(
            '/add_action\s*\(\s*[\'\"](?:after_switch_theme|woocommerce_thankyou)[\'\"]/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testLegacyHuntOrganizerAssignmentStaysOutOfTheme(): void
    {
        $violations = $this->findPhpMatches('/assigner_organisateur_automatiquement/');

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testOrganizerRoleAssignmentStaysOutOfTheme(): void {
        $violations = $this->findPhpMatches('/ajouter_role_organisateur_creation/');

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testRemovedLegacyMutationWorkflowsStayOutOfTheme(): void {
        $violations = $this->findPhpMatches(
            '/gerer_organisateur|mettre_a_jour_paiements_organisateurs|reinitialiser_enigme|'
            . 'verifier_souscription_chasse/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotWriteBusinessRecords(): void {
        $violations = $this->findPhpMatches(
            '/\bwp_(?:insert|update|delete)_post\s*\(|\b(?:update|delete|add)_(?:post|user)_meta\s*\(|'
            . '\b(?:update|delete|add)_field\s*\(|\bdelete_option\s*\(|->(?:add|set|remove)_role\s*\(/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testCoreOwnsHuntValidationCancellationWrites(): void {
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntValidationAjaxHandler.php'
        );

        self::assertStringContainsString("update_field('chasse_cache_statut', 'a_venir'", $handler);
        self::assertStringContainsString("update_field('chasse_cache_statut_validation', 'correction'", $handler);
    }

    public function testThemeDoesNotOwnGlobalWordPressAccessPolicies(): void {
        $violations = $this->findPhpMatches(
            '/add_(?:action|filter)\s*\(\s*[\'\"](?:ajax_query_attachments_args|rest_attachment_query|'
            . 'use_block_editor_for_post|user_has_cap|pre_get_posts)[\'\"]/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnBackOfficeAccessPolicy(): void {
        $violations = $this->findPhpMatches('/roles_bloques\s*=\s*\[ROLE_ORGANISATEUR/');

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnRiddlePermissionCacheInvalidation(): void {
        $violations = $this->findPhpMatches(
            '/enigme_bump_permissions_cache_version|enigme_clear_render_cache_on_solution_save|'
            . 'enigme_clear_sidebar_cache_on_solve|save_post_enigme/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnEmailBehavior(): void {
        $violations = $this->findPhpMatches(
            '/wp_new_user_notification_email|retrieve_password_notification_email|woocommerce_mail_content|'
            . 'woocommerce_email_(?:header|footer)/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnNativeContentScreenRoutes(): void {
        $violations = $this->findPhpMatches(
            '/redirection_si_acces_refuse|add_action\s*\(\s*[\'\"]load-post(?:-new)?\.php[\'\"]/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnProtectedAssetRoutes(): void {
        $violations = $this->findPhpMatches('/voir_fichier|voir_image_enigme/');

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnLegacyAccountRoutes(): void {
        $violations = $this->findPhpMatches(
            '/function\s+(?:ajouter_rewrite_rules|ajouter_query_vars|charger_template_utilisateur)\s*\(/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnSitePasswordProtection(): void {
        $violations = $this->findPhpMatches('/function\s+ca_site_password_protection|ca_site_password_protection\s*\(/');

        self::assertSame([], $violations, $this->formatViolations($violations));

        $coreProtection = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Security/site-password.php'
        );
        self::assertStringContainsString("add_action('init', 'ca_site_password_protection')", $coreProtection);
    }

    public function testThemeDoesNotOwnRiddleAccessRedirect(): void {
        $violations = $this->findPhpMatches('/handle_single_enigme_access/');

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnOrganizerContactRoute(): void {
        $violations = $this->findPhpMatches(
            '/function\s+(?:ajouter_endpoint_contact_organisateur|ajouter_query_var_contact)\s*\(/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testLegacyStatisticsResetWorkflowStaysOutOfTheme(): void
    {
        $violations = $this->findPhpMatches(
            '/admin_post_(?:reset_stats_action|toggle_reinit_stats_action)|'
            . 'traiter_reinitialisation_stats|supprimer_metas_(?:utilisateur|organisateur|globales|post)/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnConversionSettingsEntryPoints(): void
    {
        $violations = $this->findPhpMatches(
            '/init_taux_conversion|traiter_mise_a_jour_taux_conversion|traiter_demande_paiement|'
            . 'traiter_gestion_points/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDelegatesHuntModerationPolicyToCore(): void
    {
        $path = self::THEME_PATH . '/inc/admin-functions.php';
        $contents = (string) file_get_contents($path);

        self::assertStringNotContainsString('HuntModerationService', $contents);
        self::assertStringNotContainsString(
            "add_action('admin_post_traiter_validation_chasse'",
            $contents
        );
        self::assertStringNotContainsString('traiter_validation_chasse_admin', $contents);
        self::assertStringNotContainsString('HuntModerationRequestHandler::configure', $contents);
    }

    public function testHuntValidationTemplateDelegatesToCore(): void
    {
        $path = self::THEME_PATH . '/templates/page-traitement-validation-chasse.php';
        $contents = (string) file_get_contents($path);

        self::assertStringContainsString('HuntValidationRequestRouteHandler::handle()', $contents);
        self::assertStringNotContainsString('update_field(', $contents);
        self::assertStringNotContainsString('wp_verify_nonce(', $contents);
    }

    public function testThemeDoesNotOwnHuntCompletionHook(): void
    {
        $path = self::THEME_PATH . '/inc/gamify-functions.php';
        $contents = (string) file_get_contents($path);

        self::assertStringNotContainsString("add_action('enigme_resolue'", $contents);
        self::assertStringNotContainsString('function verifier_fin_de_chasse', $contents);
        self::assertStringNotContainsString('cat_get_hunt_completion_service', $contents);

        $huntFunctions = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        self::assertStringNotContainsString('function gerer_chasse_terminee', $huntFunctions);
    }

    public function testThemeDoesNotExposeAnswerSubmissionControllers(): void
    {
        $answers = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/reponses.php');

        self::assertStringNotContainsString('function soumettre_reponse_manuelle', $answers);
        self::assertStringNotContainsString('function soumettre_reponse_automatique', $answers);
        self::assertStringNotContainsString('function envoyer_mail_reponse_manuelle', $answers);
        self::assertStringNotContainsString('function envoyer_mail_resultat_joueur', $answers);
        self::assertStringNotContainsString('function envoyer_mail_accuse_reception_joueur', $answers);
    }

    public function testHuntTemplateDoesNotPersistWelcomeModalState(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/single-chasse.php');

        self::assertStringNotContainsString(
            "update_post_meta(\$chasse_id, 'chasse_modal_bienvenue_vue'",
            $template
        );
    }

    public function testHuntTemplateDoesNotRefreshBusinessCaches(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/single-chasse.php');

        self::assertStringNotContainsString('verifier_ou_recalculer_statut_chasse(', $template);
        self::assertStringNotContainsString('verifier_et_synchroniser_cache_enigmes_si_autorise(', $template);
        self::assertStringNotContainsString('verifier_ou_mettre_a_jour_cache_complet(', $template);
        self::assertStringNotContainsString('chasse_clear_infos_affichage_cache(', $template);
    }

    public function testPresentationTemplatesDoNotWritePersistentData(): void
    {
        $violations = [];
        $mutation = '/\b(?:update|delete)_(?:post|user)_meta\s*\(|\bupdate_field\s*\('
            . '|\bwp_(?:insert|update|delete)_post\s*\(|\bset_transient\s*\(|\$wpdb->(?:insert|update|delete|query)\s*\(/';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::THEME_PATH, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            $relative = str_replace(self::THEME_PATH . '/', '', $path);
            $isTemplate = preg_match('#^(?:single-|page-).*\.php$#', $relative) === 1
                || strpos($relative, 'templates/') === 0
                || strpos($relative, 'template-parts/') === 0;
            if (
                $file->isFile()
                && $file->getExtension() === 'php'
                && $isTemplate
                && preg_match($mutation, (string) file_get_contents($path)) === 1
            ) {
                $violations[] = $relative;
            }
        }

        self::assertSame([], array_values(array_unique($violations)), $this->formatViolations($violations));
    }

    public function testThemeDoesNotOwnManualAttemptReview(): void
    {
        $attempts = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/tentatives.php');

        self::assertStringNotContainsString('function traiter_tentative_manuelle', $attempts);
        self::assertStringNotContainsString('function traiter_tentative(', $attempts);
    }

    public function testThemeDoesNotRegisterOrganizerConfirmationRoutes(): void
    {
        $violations = $this->findPhpMatches(
            '/register_endpoint_confirmation_organisateur|traiter_confirmation_organisateur'
            . '|OrganizerConfirmationRouteHandler::configure/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testHuntValidationPoliciesAreNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        preg_match(
            '/HuntValidationAjaxHandler::configure\([\s\S]*?^    \);/m',
            $source,
            $matches
        );
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('utilisateur_est_organisateur_associe_a_chasse', $configuration);
        self::assertStringNotContainsString('recuperer_id_chasse_associee', $configuration);
    }

    public function testRiddleAttemptPersistenceIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/tentatives.php');
        preg_match(
            '/RiddleAttemptListAjaxHandler::configure\([\s\S]*?^    \);/m',
            $source,
            $matches
        );
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('recuperer_tentatives_enigme', $configuration);
        self::assertStringNotContainsString('compter_tentatives_enigme', $configuration);
        self::assertStringNotContainsString('utilisateur_peut_modifier_post', $configuration);
        self::assertStringNotContainsString('RiddleAttemptViewAjaxHandler::configure', $source);
    }

    public function testRiddleStatisticsAccessIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/stats.php');
        preg_match('/RiddleStatisticsAjaxHandler::configure\([\s\S]*?^    \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('utilisateur_peut_voir_panneau', $configuration);
        self::assertStringNotContainsString('utilisateur_peut_modifier_post', $configuration);
        self::assertStringNotContainsString('enigme_compter_', $configuration);
        self::assertStringNotContainsString('enigme_lister_participants', $configuration);
    }

    public function testHuntStatisticsBusinessCallbacksAreNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/chasse/stats.php');
        preg_match('/HuntStatisticsAjaxHandler::configure\([\s\S]*?^    \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('utilisateur_est_organisateur_associe_a_chasse', $configuration);
        self::assertStringNotContainsString('chasse_compter_', $configuration);
        self::assertStringNotContainsString('chasse_lister_participants', $configuration);
    }

    public function testPointsHistoryQueriesAreNotInjectedByTheme(): void
    {
        $points = (string) file_get_contents(self::THEME_PATH . '/inc/gamify-functions.php');
        $conversions = (string) file_get_contents(self::THEME_PATH . '/inc/organisateur-functions.php');

        self::assertStringNotContainsString('return get_user_points_history(', $points);
        self::assertStringNotContainsString('return cat_get_conversion_service()->getRequests(', $conversions);
    }

    public function testEngagedHuntsBusinessCallbacksAreNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        preg_match('/EngagedHuntsAjaxHandler::configure\([\s\S]*?^    \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('ca_get_user_engaged_hunt_ids', $configuration);
        self::assertStringNotContainsString('ca_prepare_engaged_hunts_pagination', $configuration);
    }

    public function testUserAttemptsQueryIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        preg_match('/UserAttemptsAjaxHandler::configure\([\s\S]*?^    \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('ca_register_tentatives_search_context', $configuration);
        self::assertStringNotContainsString('ca_get_tentatives_view_model', $configuration);
    }

    public function testThemeDoesNotOwnHuntModerationEmails(): void
    {
        $violations = $this->findPhpMatches(
            '/envoyer_mail_(?:demande_correction|chasse_validee|chasse_bannie|chasse_supprimee)/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testBusinessProcessingTemplatesDelegateToCore(): void
    {
        $engagement = (string) file_get_contents(
            self::THEME_PATH . '/templates/page-traitement-engagement.php'
        );
        $attempt = (string) file_get_contents(
            self::THEME_PATH . '/templates/page-traitement-tentative.php'
        );

        self::assertStringContainsString('HuntEngagementRouteHandler::handle()', $engagement);
        self::assertStringNotContainsString('HuntEngagementApplicationService', $engagement);
        self::assertStringContainsString('RiddleAttemptMaintenanceService', $attempt);
        self::assertStringNotContainsString('deleteForRiddle(', $attempt);
        self::assertStringNotContainsString('deleteRiddleStatuses(', $attempt);
    }

    public function testRemovedCompatibilityLoadersStayRemoved(): void
    {
        $loaders = [
            self::THEME_PATH . '/inc/PointsRepository.php',
            self::THEME_PATH . '/inc/messages/class-user-message-repository.php',
            self::THEME_PATH . '/inc/cli/class-cat-cli-command.php',
        ];

        foreach ($loaders as $loader) {
            self::assertFileDoesNotExist($loader);
        }
    }

    /** @return string[] */
    private function findPhpMatches(string $pattern): array
    {
        $violations = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::THEME_PATH, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if (strpos($path, '/tests/') !== false) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match($pattern, $contents) === 1) {
                $violations[] = str_replace(str_replace('\\', '/', self::THEME_PATH) . '/', '', $path);
            }
        }

        sort($violations);
        return $violations;
    }

    /** @param string[] $violations */
    private function formatViolations(array $violations): string
    {
        return $violations === []
            ? ''
            : "Theme/core boundary violations:\n- " . implode("\n- ", $violations);
    }
}
