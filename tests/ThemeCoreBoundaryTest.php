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

        $access = (string) file_get_contents(self::THEME_PATH . '/inc/access-functions.php');
        self::assertStringNotContainsString('function utilisateur_peut_creer_post', $access);
        self::assertStringNotContainsString('function utilisateur_peut_modifier_post', $access);

        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/ContentScreenAccessHookHandler.php'
        );
        self::assertStringNotContainsString("function_exists('utilisateur_peut_", $handler);
    }

    public function testThemeDoesNotOwnProtectedAssetRoutes(): void {
        $violations = $this->findPhpMatches('/voir_fichier|voir_image_enigme/');

        self::assertSame([], $violations, $this->formatViolations($violations));

        $imageController = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-riddle-image.php'
        );
        self::assertStringNotContainsString('trouver_chemin_image', $imageController);
        self::assertStringNotContainsString('utilisateur_peut_voir_enigme', $imageController);
        self::assertStringContainsString('ProtectedRiddleAssetService', $imageController);

        $solutionController = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/protected-solution-file.php'
        );
        self::assertStringNotContainsString('solution_recuperer_par_objet', $solutionController);
        self::assertStringNotContainsString('utilisateur_peut_voir_solution_', $solutionController);
        self::assertStringNotContainsString('cat_debug', $solutionController);
        self::assertStringContainsString('ProtectedSolutionAssetService', $solutionController);

        $adminController = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Admin/AdminAjaxHandler.php'
        );
        self::assertStringNotContainsString('cat_debug', $adminController);
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

        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleAccessRedirectHandler.php'
        );
        foreach ([
            'recuperer_id_chasse_associee',
            'verifier_et_synchroniser_cache_enigmes_si_autorise',
            'utilisateur_est_engage_dans_chasse',
            'utilisateur_est_engage_dans_enigme',
            'utilisateur_peut_engager_enigme',
            'marquer_enigme_comme_engagee',
            'enigme_est_visible_pour',
            'enigme_pre_requis_remplis',
            'utilisateur_peut_modifier_enigme',
            'verifier_ou_mettre_a_jour_cache_complet',
            'utilisateur_est_organisateur_associe_a_chasse',
            'compter_tentatives_en_attente',
        ] as $themeHelper) {
            self::assertStringNotContainsString($themeHelper, $handler);
        }
    }

    public function testThemeDoesNotOwnOrganizerContactRoute(): void {
        $violations = $this->findPhpMatches(
            '/function\s+(?:ajouter_endpoint_contact_organisateur|ajouter_query_var_contact)\s*\(/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testThemeDoesNotDeclareRelationshipCompatibilityFunctions(): void
    {
        $themePath = self::THEME_PATH . '/inc/relations-functions.php';
        $corePath = __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Relationships/relationship-functions.php';

        preg_match_all(
            '/\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/',
            (string) file_get_contents($themePath),
            $themeMatches
        );
        self::assertSame([], $themeMatches[1]);

        preg_match_all(
            '/\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/',
            (string) file_get_contents($corePath),
            $coreMatches
        );
        $coreFunctions = $coreMatches[1];
        foreach ([
            'recuperer_chasse_associee',
            'get_chasses_de_organisateur',
            'recuperer_enigmes_pour_chasse',
            'synchroniser_cache_enigmes_chasse',
            'verifier_et_synchroniser_cache_enigmes_si_autorise',
        ] as $functionName) {
            self::assertContains($functionName, $coreFunctions);
        }
    }

    public function testThemeDoesNotDeclareHuntDataAndProgressCompatibilityFunctions(): void
    {
        $theme = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        $statistics = (string) file_get_contents(self::THEME_PATH . '/inc/chasse/stats.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/hunt-functions.php'
        );

        foreach ([
            'recuperer_infos_chasse',
            'chasse_install_winners_table',
            'enregistrer_gagnant_chasse',
            'compter_chasses_gagnees',
            'chasse_get_champs',
            'utilisateur_est_engage_dans_chasse',
            'chasse_calculer_progression_utilisateur',
            'compter_joueurs_engages_chasse',
            'enregistrer_engagement_chasse',
        ] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $theme);
            self::assertStringContainsString("function {$functionName}(", $core);
        }

        self::assertStringNotContainsString('function cat_get_hunt_engagement_service(', $statistics);
    }

    public function testThemeDoesNotDeclareSolutionQueryAndVisibilityFunctions(): void
    {
        $theme = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/solution-functions.php'
        );

        foreach ([
            'solution_recuperer_par_objet',
            'solution_existe_pour_objet',
            'solution_peut_etre_affichee',
            'solution_chasse_peut_etre_affichee',
        ] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $theme);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
    }

    public function testThemeDoesNotOwnHuntCtaViewModel(): void
    {
        $theme = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/hunt-cta-functions.php'
        );

        foreach ([
            'generer_cta_chasse',
            'render_form_validation_chasse',
            'render_form_annulation_validation_chasse',
            'trouver_chasse_a_valider',
            'traiter_annulation_validation_chasse',
            'actualiser_cta_validation_chasse',
            'cat_build_hunt_validation_cta',
        ] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $theme);
            self::assertStringContainsString("function {$functionName}(", $core);
        }

        self::assertStringNotContainsString('HuntValidationAjaxHandler::configure', $theme);
        self::assertStringContainsString('HuntValidationAjaxHandler::configure', $core);
    }

    public function testThemeDoesNotOwnAccessCompatibilityFunctions(): void
    {
        $theme = (string) file_get_contents(self::THEME_PATH . '/inc/access-functions.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/access-functions.php'
        );

        foreach ([
            'utilisateur_peut_voir_statistiques_chasse',
            'est_organisateur',
            'indice_action_autorisee',
            'solution_action_autorisee',
            'utilisateur_peut_voir_enigme',
            'utilisateur_peut_ajouter_enigme',
            'utilisateur_peut_modifier_enigme',
            'utilisateur_peut_supprimer_enigme',
            'utilisateur_peut_ajouter_chasse',
            'utilisateur_peut_voir_panneau',
            'utilisateur_peut_editer_champs',
            'champ_est_editable',
            'utilisateur_peut_voir_solution_enigme',
            'utilisateur_peut_voir_solution_chasse',
            'verifier_et_enregistrer_condition_pre_requis',
            'chasse_est_visible_pour_utilisateur',
        ] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $theme);
            self::assertStringContainsString("function {$functionName}(", $core);
        }

        self::assertStringNotContainsString('cat_debug', $core);
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
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/hunt-cta-functions.php'
        );

        self::assertStringNotContainsString('HuntValidationAjaxHandler::configure', $source);
        self::assertStringContainsString('HuntValidationAjaxHandler::configure', $core);
    }

    public function testRiddleAttemptRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/tentatives.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptListAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptListRenderer.php'
        );

        self::assertStringNotContainsString('RiddleAttemptListAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function cat_render_riddle_attempt_list', $source);
        self::assertStringContainsString("[new RiddleAttemptListRenderer(), 'render']", $handler);
        self::assertStringContainsString('class RiddleAttemptListRenderer', $renderer);
        self::assertStringNotContainsString('RiddleAttemptViewAjaxHandler::configure', $source);
    }

    public function testThemeDoesNotOwnSharedTableHelpers(): void
    {
        $theme = (string) file_get_contents(self::THEME_PATH . '/inc/table.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Support/table-functions.php'
        );

        foreach (['cta_prepare_masked_proposition_options', 'cta_render_proposition_cell'] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $theme);
            self::assertStringContainsString("function {$functionName}(", $core);
        }

        $themePager = (string) file_get_contents(self::THEME_PATH . '/inc/pager.php');
        $corePager = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Support/pager-functions.php'
        );
        self::assertStringNotContainsString('function cta_render_pager(', $themePager);
        self::assertStringContainsString('function cta_render_pager(', $corePager);
    }

    public function testThemeDoesNotOwnRiddleMutationPoliciesOrLifecycle(): void
    {
        $edition = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-enigme.php');
        foreach ([
            'chassesautresor_riddle_created',
            'chassesautresor_can_modify_riddle',
            'chassesautresor_can_edit_riddle_fields',
            'chassesautresor_riddle_state_refresh_requested',
            'chassesautresor_riddle_completeness_refresh_requested',
        ] as $businessHook) {
            self::assertStringNotContainsString($businessHook, $edition);
        }

        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleFieldMutationAjaxHandler.php'
        );
        self::assertStringNotContainsString("apply_filters('chassesautresor_can_modify_riddle'", $handler);
        self::assertStringNotContainsString("apply_filters('chassesautresor_can_edit_riddle_fields'", $handler);
    }

    public function testThemeDoesNotOwnHuntMutationPoliciesOrLifecycle(): void
    {
        $edition = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-chasse.php');
        foreach ([
            'chassesautresor_can_edit_hunt_dates',
            'chassesautresor_hunt_dates_updated',
            'chassesautresor_can_modify_hunt',
            'chassesautresor_can_edit_hunt_fields',
            'chassesautresor_apply_hunt_closure',
            'chassesautresor_hunt_fields_updated',
        ] as $businessHook) {
            self::assertStringNotContainsString($businessHook, $edition);
        }

        $fieldHandler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFieldMutationAjaxHandler.php'
        );
        self::assertStringNotContainsString("apply_filters('chassesautresor_can_", $fieldHandler);
        self::assertStringNotContainsString("'chassesautresor_apply_hunt_closure'", $fieldHandler);
    }

    public function testThemeDoesNotOwnOrganizerOrHintMutationPolicies(): void
    {
        $organizer = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-organisateur.php');
        self::assertStringNotContainsString('chassesautresor_can_modify_organizer', $organizer);
        self::assertStringNotContainsString('chassesautresor_can_edit_organizer_fields', $organizer);

        $hint = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-indice.php');
        self::assertStringNotContainsString('chassesautresor_can_manage_hint', $hint);
        self::assertStringNotContainsString('chassesautresor_can_modify_hint', $hint);
        self::assertStringNotContainsString('chassesautresor_can_edit_hint_fields', $hint);

        foreach (['OrganizerFieldMutationAjaxHandler.php', 'HintFieldMutationAjaxHandler.php'] as $filename) {
            $handler = (string) file_get_contents(
                __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/' . $filename
            );
            self::assertStringNotContainsString("apply_filters('chassesautresor_can_", $handler);
        }
    }

    public function testHintQueriesAreNotInjectedByTheme(): void
    {
        $edition = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-indice.php');
        foreach ([
            'chassesautresor_hint_hunt_riddle_ids',
            'chassesautresor_hint_related_hunt_id',
            'chassesautresor_hint_target_riddles',
            'chassesautresor_next_hint_rank',
            'chassesautresor_hint_target_has_solution',
        ] as $businessHook) {
            self::assertStringNotContainsString($businessHook, $edition);
        }

        $table = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintTableAjaxHandler.php'
        );
        $options = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintRiddleOptionsAjaxHandler.php'
        );
        self::assertStringNotContainsString('chassesautresor_hint_', $table);
        self::assertStringNotContainsString('chassesautresor_hint_', $options);
    }

    public function testSolutionPoliciesAndQueriesAreNotInjectedByTheme(): void
    {
        $edition = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-solution.php');
        foreach ([
            'chassesautresor_can_manage_solution',
            'chassesautresor_hunt_riddle_ids',
            'chassesautresor_hunt_riddles',
            'chassesautresor_solution_exists',
        ] as $businessHook) {
            self::assertStringNotContainsString($businessHook, $edition);
        }
        self::assertStringContainsString('chassesautresor_render_solutions_table', $edition);

        $management = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionManagementAjaxHandler.php'
        );
        self::assertStringNotContainsString('chassesautresor_can_manage_solution', $management);
        self::assertStringNotContainsString('chassesautresor_hunt_riddle', $management);
        self::assertStringNotContainsString('chassesautresor_solution_exists', $management);
    }

    public function testThemeDoesNotOwnRiddleImageProtectionLifecycle(): void
    {
        $edition = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-securite.php');
        foreach ([
            'acf/upload_prefilter/name=enigme_visuel_image',
            'acf/upload_file/name=enigme_visuel_image',
            'verrouiller_visuels_enigme_si_nouveau_upload',
            'chassesautresor_can_manage_riddle_images',
        ] as $businessHook) {
            self::assertStringNotContainsString($businessHook, $edition);
        }
        self::assertStringContainsString('acf/format_value/type=gallery', $edition);
    }

    public function testThemeDoesNotOwnHuntDisplayCacheInvalidation(): void
    {
        $violations = $this->findPhpMatches(
            '/function chasse_(?:clear|invalidate|acf_clear)_infos_affichage_cache/'
        );

        self::assertSame([], $violations, $this->formatViolations($violations));

        $huntFunctions = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        self::assertStringNotContainsString('chasse_acf_handle_utilisateurs_associes', $huntFunctions);
        self::assertStringNotContainsString('chasse_clear_infos_affichage_cache_for_organisateur', $huntFunctions);

        $status = (string) file_get_contents(self::THEME_PATH . '/inc/statut-functions.php');
        self::assertStringNotContainsString(
            'chassesautresor_hunt_display_cache_clear_requested',
            $status
        );
    }

    public function testThemeDoesNotOwnAvatarPersistenceOrPolicyHooks(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');

        self::assertStringNotContainsString('function upload_user_avatar', $source);
        self::assertStringNotContainsString('function autoriser_avatars_upload', $source);
        self::assertStringNotContainsString('function remplacer_avatar_utilisateur', $source);
        self::assertStringNotContainsString("add_filter('upload_mimes'", $source);
        self::assertStringNotContainsString("add_filter('get_avatar'", $source);
        self::assertStringContainsString('charger_script_avatar_upload', $source);
    }

    public function testThemeDoesNotOwnHuntDateValidationOrHintRedirect(): void
    {
        $hunt = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        $hint = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-indice.php');

        self::assertStringNotContainsString('acf/validate_value/name=date_de_fin', $hunt);
        self::assertStringNotContainsString('rediriger_si_affichage_indice', $hint);
        self::assertStringNotContainsString("add_action('template_redirect'", $hint);
    }

    public function testThemeDoesNotOwnOrganizerRequestLifecycle(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/organisateur-functions.php');
        $template = (string) file_get_contents(
            self::THEME_PATH . '/templates/page-confirmation-organisateur.php'
        );

        foreach ([
            'function cat_clear_organisateur_request',
            'function cat_get_organisateur_request_status',
            'function lancer_demande_organisateur',
            'function renvoyer_email_confirmation_organisateur',
            'function confirmer_demande_organisateur',
        ] as $businessFunction) {
            self::assertStringNotContainsString($businessFunction, $source);
        }

        self::assertStringNotContainsString('confirmer_demande_organisateur', $template);
        self::assertStringNotContainsString('WP_User', $source);
    }

    public function testThemeDoesNotOwnHuntValidationPolicyOrCorrectionCleanup(): void
    {
        $hunt = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');
        $account = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        $route = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntValidationRequestRouteHandler.php'
        );

        self::assertStringNotContainsString('function peut_valider_chasse', $hunt);
        self::assertStringNotContainsString('function myaccount_clear_correction_message', $account);
        self::assertStringContainsString('HuntValidationAccessResolver', $route);
        self::assertStringContainsString('HuntCorrectionMessageService', $route);
        self::assertStringNotContainsString('peut_valider_chasse(', $route);
        self::assertStringNotContainsString('myaccount_clear_correction_message(', $route);
    }

    public function testThemeDoesNotOwnHuntValidationAccountMessageLifecycle(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');

        self::assertStringNotContainsString('function myaccount_maybe_add_validation_message', $source);
        self::assertStringNotContainsString(
            "add_action('template_redirect', 'myaccount_maybe_add_validation_message')",
            $source
        );
    }

    public function testThemeDoesNotOwnAccountMessagePersistenceOrOrganizerCreation(): void
    {
        $account = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        $organizer = (string) file_get_contents(
            self::THEME_PATH . '/inc/edition/edition-organisateur.php'
        );

        foreach ([
            'function cat_get_account_message_service',
            'function myaccount_add_persistent_message',
            'function myaccount_remove_persistent_message',
            'function myaccount_add_flash_message',
        ] as $persistenceFunction) {
            self::assertStringNotContainsString($persistenceFunction, $account);
        }

        self::assertStringNotContainsString('function creer_organisateur_pour_utilisateur', $organizer);
        self::assertStringNotContainsString("'wp_insert_post'", $organizer);
        self::assertStringNotContainsString("'update_field'", $organizer);
    }

    public function testThemeDoesNotOwnBackOfficeAccessOrOrganizerUserPolicy(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-core.php');

        foreach ([
            'acf_restreindre_utilisateurs_associes',
            'restreindre_admin_menu_pour_roles_non_admins',
            'masquer_admin_interface_pour_non_admins',
            "add_filter('show_admin_bar'",
            "add_action('admin_init'",
        ] as $businessPolicy) {
            self::assertStringNotContainsString($businessPolicy, $source);
        }
    }

    public function testThemeDoesNotOwnSolutionLifecycleCompatibilityFunctions(): void
    {
        $solution = (string) file_get_contents(
            self::THEME_PATH . '/inc/edition/edition-solution.php'
        );
        $edition = (string) file_get_contents(self::THEME_PATH . '/inc/edition/edition-core.php');

        foreach ([
            'function rediriger_si_affichage_solution',
            'function creer_solution_pour_objet',
            'function register_endpoint_creer_solution',
            'function flush_rewrite_rules_creer_solution',
            'function solution_planifier_publication',
            'function solution_rendre_accessible',
            'function basculer_solutions_programme',
            'function planifier_tache_basculer_solutions_programme',
            'function mettre_a_jour_cache_solution',
            'function solution_acf_save_post',
        ] as $lifecycleFunction) {
            self::assertStringNotContainsString($lifecycleFunction, $solution);
        }

        self::assertStringNotContainsString('verifier_ou_mettre_a_jour_cache_complet', $edition);
    }

    public function testThemeDoesNotOwnHintRelationshipFieldPolicy(): void
    {
        $source = (string) file_get_contents(
            self::THEME_PATH . '/inc/edition/edition-indice.php'
        );

        self::assertStringNotContainsString('pre_remplir_indice_chasse_linked', $source);
        self::assertStringNotContainsString('acf/load_field/name=indice_chasse_linked', $source);
        self::assertStringNotContainsString('recuperer_id_chasse_associee', $source);
        self::assertStringNotContainsString('function creer_indice_pour_objet', $source);
        self::assertStringNotContainsString('function register_endpoint_creer_indice', $source);
        self::assertStringNotContainsString('function flush_rewrite_rules_creer_indice', $source);

        $creation = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCreationRouteHandler.php'
        );
        $deletion = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintDeletionAjaxHandler.php'
        );
        self::assertStringNotContainsString('chassesautresor_can_manage_hint', $creation);
        self::assertStringNotContainsString('chassesautresor_can_manage_hint', $deletion);
    }

    public function testThemeDoesNotOwnRiddleAccessConditionFieldPolicy(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/access-functions.php');

        self::assertStringNotContainsString('acf/load_field/name=enigme_acces_condition', $source);
        self::assertStringNotContainsString('recuperer_enigmes_possibles_pre_requis', $source);
    }

    public function testThemeDoesNotOwnScheduledHuntStatusRefresh(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/statut-functions.php');

        self::assertStringNotContainsString('cat_check_stale_hunt_status', $source);
        self::assertStringNotContainsString('chassesautresor_hunt_status_stale_check_requested', $source);
        foreach ([
            'verifier_ou_recalculer_statut_chasse',
            'mettre_a_jour_statuts_chasse',
            'forcer_recalcul_statut_chasse',
            'recuperer_statut_chasse',
            'forcer_statut_apres_acf',
            'schedule_cat_recalculate_chasse_statuses',
            'cat_recalculate_chasse_statuses',
        ] as $legacyFunction) {
            self::assertStringNotContainsString('function ' . $legacyFunction, $source);
        }

        $moderation = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntModerationRequestHandler.php'
        );
        self::assertStringNotContainsString("'mettre_a_jour_statuts_chasse'", $moderation);
        self::assertStringNotContainsString("'enigme_mettre_a_jour_etat_systeme'", $moderation);
        self::assertStringNotContainsString('recuperer_enigmes_associees', $moderation);
        self::assertStringNotContainsString('get_organisateur_from_chasse', $moderation);
        self::assertStringNotContainsString('chasse_trash_with_children', $moderation);
    }

    public function testThemeDoesNotOwnRiddleSystemStateCompatibilityApi(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/statut-functions.php');
        foreach ([
            'mettre_a_jour_statuts_enigmes_de_la_chasse',
            'enigme_mettre_a_jour_etat_systeme',
            'enigme_mettre_a_jour_etat_systeme_automatiquement',
            'forcer_recalcul_statut_enigme',
            'enigme_get_etat_systeme',
        ] as $legacyFunction) {
            self::assertStringNotContainsString('function ' . $legacyFunction, $source);
        }
    }

    public function testThemeDoesNotOwnCompletionCompatibilityApi(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/statut-functions.php');
        foreach ([
            'cat_get_completion_cache_manager',
            'organisateur_est_complet',
            'organisateur_mettre_a_jour_complet',
            'chasse_has_validatable_enigme',
            'chasse_est_complet',
            'chasse_mettre_a_jour_complet',
            'enigme_est_complet',
            'enigme_mettre_a_jour_complet',
            'mettre_a_jour_cache_complet_automatiquement',
            'verifier_ou_mettre_a_jour_cache_complet',
        ] as $legacyFunction) {
            self::assertStringNotContainsString('function ' . $legacyFunction, $source);
        }
    }

    public function testThemeDoesNotOwnRiddleProgressCompatibilityApi(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/statut-functions.php');
        foreach ([
            'cat_get_hunt_progress_service',
            'enigme_get_bonnes_reponses',
            'enigme_get_statut_utilisateur',
            'enigme_mettre_a_jour_statut_utilisateur',
            'enigme_pre_requis_remplis',
            'get_statut_utilisateur_enigme',
            'est_enigme_resolue_par_utilisateur',
            'enigme_verifier_verrouillage',
            'traiter_statut_enigme',
            'enigme_est_visible_pour',
            'utilisateur_peut_engager_enigme',
        ] as $legacyFunction) {
            self::assertStringNotContainsString('function ' . $legacyFunction, $source);
        }
    }

    public function testThemeDoesNotOwnHuntStatusBadgePolicy(): void
    {
        $status = (string) file_get_contents(self::THEME_PATH . '/inc/statut-functions.php');
        $badge = (string) file_get_contents(self::THEME_PATH . '/inc/badge-functions.php');

        self::assertStringNotContainsString('cat_render_hunt_status_badge', $status);
        self::assertStringNotContainsString('is_canevas_creation', $status);
        self::assertStringNotContainsString('function chasse_preparer_badge_statut', $badge);
    }

    public function testThemeDoesNotRedeclareCoreGlobalFunctions(): void
    {
        $pluginPath = __DIR__ . '/../wp-content/plugins/chassesautresor-core';
        $coreFunctions = $this->findDeclaredFunctions($pluginPath);
        $themeFunctions = $this->findDeclaredFunctions(self::THEME_PATH);

        self::assertSame([], array_values(array_intersect($themeFunctions, $coreFunctions)));
    }

    public function testThemeDoesNotOwnOrganizerRelationshipCompatibilityApi(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/relations-functions.php');
        foreach ([
            'cat_get_organizer_service',
            'cat_get_relationship_service',
            'get_organisateur_from_user',
            'get_organisateur_chasse',
            'get_organisateur_from_chasse',
            'get_organisateur_id_from_context',
            'utilisateur_est_organisateur_associe_a_chasse',
        ] as $legacyFunction) {
            self::assertStringNotContainsString('function ' . $legacyFunction, $source);
        }
    }

    public function testThemeDoesNotResolveProtectedImagePaths(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/visuels.php');

        self::assertStringNotContainsString('function trouver_chemin_image', $source);
        self::assertStringNotContainsString("'trouver_chemin_image'", $source);
        self::assertStringNotContainsString('wp_cache_set(', $source);
    }

    public function testThemeDoesNotBuildRiddleSidebarStatisticsCaches(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');

        self::assertStringNotContainsString("wp_cache_set(\$cache_key, \$data", $source);
        self::assertStringNotContainsString("wp_cache_set(\$cache_key, \$rate", $source);
        self::assertStringNotContainsString('chasse_calculer_taux_engagement(', $source);
    }

    public function testThemeDoesNotPersistHuntDisplayViewModels(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/chasse-functions.php');

        self::assertStringNotContainsString("wp_cache_set(\$cache_key", $source);
        self::assertStringNotContainsString("set_transient(\$cache_key", $source);
        self::assertStringContainsString('HuntDisplayViewCacheService', $source);
    }

    public function testThemeDoesNotWriteCaches(): void
    {
        $violations = $this->findPhpMatches('/\b(?:wp_cache_set|set_transient)\s*\(/');

        self::assertSame([], $violations, $this->formatViolations($violations));
    }

    public function testRiddleStatisticsRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/stats.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStatisticsParticipantRenderer.php'
        );

        self::assertStringNotContainsString('RiddleStatisticsAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function cat_render_riddle_statistics_participants', $source);
        self::assertStringContainsString("[new RiddleStatisticsParticipantRenderer(), 'render']", $handler);
        self::assertStringContainsString('class RiddleStatisticsParticipantRenderer', $renderer);
    }

    public function testHuntStatisticsRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/chasse/stats.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatisticsAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatisticsParticipantRenderer.php'
        );

        self::assertStringNotContainsString('HuntStatisticsAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function cat_render_hunt_statistics_participants', $source);
        self::assertStringContainsString("[new HuntStatisticsParticipantRenderer(), 'render']", $handler);
        self::assertStringContainsString('class HuntStatisticsParticipantRenderer', $renderer);
    }

    public function testPointsHistoryQueriesAreNotInjectedByTheme(): void
    {
        $points = (string) file_get_contents(self::THEME_PATH . '/inc/gamify-functions.php');
        $conversions = (string) file_get_contents(self::THEME_PATH . '/inc/organisateur-functions.php');

        self::assertStringNotContainsString('return get_user_points_history(', $points);
        self::assertStringNotContainsString('return cat_get_conversion_service()->getRequests(', $conversions);
    }

    public function testPointsHistoryRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/gamify-functions.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsHistoryAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsHistoryRenderer.php'
        );

        self::assertStringNotContainsString('PointsHistoryAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function cat_render_points_history_rows', $source);
        self::assertStringNotContainsString('function format_points_history_reason', $source);
        self::assertStringContainsString("[new PointsHistoryRenderer(), 'rows']", $handler);
        self::assertStringContainsString('class PointsHistoryRenderer', $renderer);
    }

    public function testConversionHistoryRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/organisateur-functions.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionHistoryAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionHistoryRenderer.php'
        );

        self::assertStringNotContainsString('ConversionHistoryAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function cat_render_conversion_history_rows', $source);
        self::assertStringContainsString("[new ConversionHistoryRenderer(), 'rows']", $handler);
        self::assertStringContainsString('class ConversionHistoryRenderer', $renderer);
    }

    public function testEngagedHuntsRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/EngagedHuntsAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/EngagedHuntsRenderer.php'
        );
        $recommendations = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/EngagedHuntsRecommendationService.php'
        );
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/engaged-hunt-functions.php'
        );

        self::assertStringNotContainsString('EngagedHuntsAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function ca_render_engaged_hunts_ajax_content', $source);
        self::assertStringNotContainsString('function ca_ajax_get_engaged_hunts', $source);
        self::assertStringContainsString("[new EngagedHuntsRenderer(), 'render']", $handler);
        self::assertStringContainsString('class EngagedHuntsRenderer', $renderer);
        self::assertStringNotContainsString('get_template_part', $renderer);
        self::assertStringNotContainsString('get_stylesheet_directory', $renderer);
        self::assertStringContainsString('class EngagedHuntsRecommendationService', $recommendations);
        foreach ([
            'ca_get_engaged_hunts_page_param',
            'ca_get_user_engaged_hunt_ids',
            'ca_prepare_engaged_hunts_pagination',
        ] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $source);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
    }

    public function testUserAttemptsRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserAttemptsAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/UserAttemptsRenderer.php'
        );

        self::assertStringNotContainsString('UserAttemptsAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function ca_render_tentatives_rows', $source);
        self::assertStringContainsString("[new UserAttemptsRenderer(), 'rows']", $handler);
        self::assertStringContainsString("[new UserAttemptsRenderer(), 'pager']", $handler);
        self::assertStringContainsString('class UserAttemptsRenderer', $renderer);
    }

    public function testConversionAccessIsNotInjectedByTheme(): void
    {
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionModalAjaxHandler.php'
        );

        self::assertStringNotContainsString('verifier_acces_conversion', $handler);
        self::assertStringContainsString('ConversionAccessService', $handler);
    }

    public function testHuntNavigationAccessIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/sidebar.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntNavigationAjaxHandler.php'
        );
        $builder = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/hunt-navigation-functions.php'
        );

        self::assertStringNotContainsString('HuntNavigationAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function sidebar_prepare_chasse_nav', $source);
        self::assertStringContainsString("?? 'sidebar_prepare_chasse_nav'", $handler);
        self::assertStringContainsString('function sidebar_prepare_chasse_nav', $builder);
    }

    public function testRiddleSidebarRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleSidebarAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleSidebarRenderer.php'
        );

        self::assertStringNotContainsString('RiddleSidebarAjaxHandler::configure', $source);
        self::assertStringContainsString("[self::renderer(), 'winners']", $handler);
        self::assertStringContainsString("[self::renderer(), 'progression']", $handler);
        self::assertStringContainsString('class RiddleSidebarRenderer', $renderer);
        self::assertStringNotContainsString('get_template_part', $renderer);
    }

    public function testHintUnlockRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/indices.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HintUnlockRenderer.php'
        );

        self::assertStringNotContainsString('HintUnlockAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function cat_render_unlocked_hint', $source);
        self::assertStringContainsString("[new HintUnlockRenderer(), 'render']", $handler);
        self::assertStringContainsString('class HintUnlockRenderer', $renderer);
    }

    public function testConversionModalRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/organisateur-functions.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionModalAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionModalRenderer.php'
        );

        self::assertStringNotContainsString('ConversionModalAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function render_conversion_modal_content', $source);
        self::assertStringContainsString('new ConversionModalRenderer()', $handler);
        self::assertStringContainsString('class ConversionModalRenderer', $renderer);
    }

    public function testAdminPaymentRenderingIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/admin-functions.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Admin/AdminAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Admin/AdminPaymentRenderer.php'
        );

        self::assertStringNotContainsString('AdminAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function render_tableau_paiements_admin', $source);
        self::assertStringContainsString('new AdminPaymentRenderer', $handler);
        self::assertStringContainsString('class AdminPaymentRenderer', $renderer);
    }

    public function testHomepageHuntFilterIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/homepage-filters.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFilterAjaxHandler.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntCardRenderer.php'
        );

        self::assertStringNotContainsString('HuntFilterAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function ca_render_filtered_hunts', $source);
        self::assertStringContainsString('new HuntCardRenderer()', $handler);
        self::assertStringNotContainsString('get_template_part', $renderer);
    }

    public function testAccountImportantMessagesAreNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        $handler = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountSectionAjaxHandler.php'
        );

        self::assertStringNotContainsString('AccountSectionAjaxHandler::configure', $source);
        self::assertStringNotContainsString('function ca_render_admin_section', $source);
        self::assertStringContainsString('new AccountSectionRenderer()', $handler);
        self::assertStringNotContainsString('function myaccount_get_important_messages', $source);
        self::assertStringNotContainsString('function myaccount_get_persistent_messages', $source);
        self::assertStringNotContainsString('function myaccount_get_flash_messages', $source);

        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/important-messages.php'
        );
        self::assertStringNotContainsString('recuperer_organisateurs_pending', $core);
        self::assertStringNotContainsString('cat_get_conversion_service', $core);
        self::assertStringNotContainsString('est_organisateur(', $core);
        self::assertStringNotContainsString('get_organisateur_from_user', $core);
        self::assertStringNotContainsString('recuperer_id_chasse_associee', $core);
    }

    public function testAccountConversionSettingsFunctionsBelongToCore(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/admin-functions.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/conversion-settings-functions.php'
        );

        foreach (['get_taux_conversion_actuel', 'update_taux_conversion'] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $source);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
    }

    public function testPointServiceFactoriesBelongToCore(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/gamify-functions.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/point-service-functions.php'
        );

        foreach ([
            'cat_get_points_service',
            'cat_get_purchase_points_service',
            'cat_get_conversion_service',
        ] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $source);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
    }

    public function testAccountStatisticsRenderingBelongsToCore(): void
    {
        $template = (string) file_get_contents(
            self::THEME_PATH . '/templates/myaccount/content-statistiques.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountStatisticsRenderer.php'
        );

        self::assertStringNotContainsString('cat_get_points_service()', $template);
        self::assertStringNotContainsString('compter_chasses_gagnees(', $template);
        self::assertStringContainsString('new ChassesAuTresor\\Core\\Messages\\AccountStatisticsRenderer', $template);
        self::assertStringContainsString('class AccountStatisticsRenderer', $renderer);
    }

    public function testAccountToolsRenderingBelongsToCore(): void
    {
        $template = (string) file_get_contents(self::THEME_PATH . '/templates/myaccount/content-outils.php');
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountToolsRenderer.php'
        );

        self::assertStringNotContainsString('get_taux_conversion_actuel()', $template);
        self::assertStringNotContainsString('get_option(', $template);
        self::assertStringContainsString('new ChassesAuTresor\\Core\\Messages\\AccountToolsRenderer', $template);
        self::assertStringContainsString('class AccountToolsRenderer', $renderer);
    }

    public function testOrganizerModerationAttemptHelpersBelongToCore(): void
    {
        $attempts = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/tentatives.php');
        $cta = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/cta.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/riddle-attempt-functions.php'
        );

        foreach (['cat_get_riddle_attempt_service', 'compter_tentatives_en_attente'] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $attempts);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
        self::assertStringNotContainsString('function enigme_normaliser_mode_validation(', $cta);
        self::assertStringContainsString('function enigme_normaliser_mode_validation(', $core);
    }

    public function testOrganizerModerationRenderingBelongsToCore(): void
    {
        $admin = (string) file_get_contents(self::THEME_PATH . '/inc/admin-functions.php');
        $template = (string) file_get_contents(
            self::THEME_PATH . '/templates/myaccount/content-organisateurs.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountOrganizersRenderer.php'
        );

        self::assertStringNotContainsString('function recuperer_organisateurs_pending(', $admin);
        self::assertStringNotContainsString('function afficher_tableau_organisateurs_pending(', $admin);
        self::assertStringContainsString('new ChassesAuTresor\\Core\\Messages\\AccountOrganizersRenderer', $template);
        self::assertStringContainsString('class AccountOrganizersRenderer', $renderer);
    }

    public function testRiddleDisplayPoliciesBelongToCore(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        $stats = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/stats.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/riddle-display-functions.php'
        );

        foreach (['cat_get_riddle_statistics_service', 'enigme_user_can_see_menu'] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $source);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
        self::assertStringNotContainsString('function cat_get_riddle_statistics_service(', $stats);
    }

    public function testRiddleStatisticsBarsBelongToCore(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        $functions = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/riddle-display-functions.php'
        );
        $renderer = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleBarRenderer.php'
        );

        foreach ([
            'enigme_render_bar_row',
            'enigme_render_bar_section',
            'enigme_render_bar_subsection',
            'enigme_render_single_bar_subsection',
        ] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $source);
            self::assertStringContainsString("function {$functionName}(", $functions);
        }
        self::assertStringNotContainsString('get_template_part', $renderer);
    }

    public function testRiddleSidebarRatesBelongToCore(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/riddle-display-functions.php'
        );

        foreach (['enigme_sidebar_progression_html', 'enigme_sidebar_resolution_html'] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $source);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
        self::assertStringContainsString('CoreServiceFactory::riddleSidebarStatistics($wpdb)', $core);
    }

    public function testRiddleSidebarMetadataAndWinnersBelongToCore(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        $core = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/riddle-display-functions.php'
        );

        foreach (['enigme_sidebar_metas_html', 'enigme_sidebar_gagnants_html'] as $functionName) {
            self::assertStringNotContainsString("function {$functionName}(", $source);
            self::assertStringContainsString("function {$functionName}(", $core);
        }
        self::assertStringNotContainsString('get_template_part', $core);
    }

    public function testRiddleParticipationHintQueriesBelongToCore(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        $service = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleParticipationService.php'
        );

        self::assertStringNotContainsString("'post_type'      => 'indice'", $source);
        self::assertStringContainsString('new ChassesAuTresor\\Core\\Progress\\RiddleParticipationService', $source);
        self::assertStringContainsString("'post_type' => 'indice'", $service);
        self::assertStringContainsString("'indice_enigme_linked'", $service);
        self::assertStringContainsString("'indice_chasse_linked'", $service);
        self::assertStringNotContainsString("get_field('indice_cout_points'", $source);
        self::assertStringNotContainsString('indice_est_debloque(', $source);
        self::assertStringNotContainsString('date_create_from_format(', $source);
        self::assertStringNotContainsString('get_indice_title(', $service);
        self::assertStringNotContainsString('recuperer_id_chasse_associee(', $service);
    }

    public function testRiddleParticipationBalancesAndAttemptCountsBelongToCore(): void
    {
        $gamify = (string) file_get_contents(self::THEME_PATH . '/inc/gamify-functions.php');
        $attempts = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/tentatives.php');
        $pointsCore = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Points/point-service-functions.php'
        );
        $attemptsCore = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/riddle-attempt-functions.php'
        );

        self::assertStringNotContainsString('function get_user_points(', $gamify);
        self::assertStringContainsString('function get_user_points(', $pointsCore);
        self::assertStringNotContainsString('function compter_tentatives_du_jour(', $attempts);
        self::assertStringContainsString('function compter_tentatives_du_jour(', $attemptsCore);
    }

    public function testRiddleParticipationInformationViewModelBelongsToCore(): void {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        $service = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleParticipationInfoService.php'
        );
        preg_match(
            '/function render_enigme_participation\(.*?(?=\n    \/\*\*\n     \* Render the solution)/s',
            $source,
            $matches
        );
        $participationSource = $matches[0] ?? '';

        self::assertStringContainsString('RiddleParticipationInfoService', $participationSource);
        self::assertStringContainsString('function build(', $service);
        self::assertStringNotContainsString("get_field('enigme_mode_validation'", $participationSource);
        self::assertStringNotContainsString("get_field('enigme_tentative_cout_points'", $participationSource);
        self::assertStringNotContainsString("get_field('enigme_tentative_max'", $participationSource);
        self::assertStringNotContainsString('get_user_points(', $participationSource);
        self::assertStringNotContainsString('compter_tentatives_du_jour(', $participationSource);
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

    /** @return string[] */
    private function findDeclaredFunctions(string $root): array
    {
        $functions = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (!$file->isFile() || $file->getExtension() !== 'php' || strpos($path, '/tests/') !== false) {
                continue;
            }
            preg_match_all(
                '/\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/',
                (string) file_get_contents($file->getPathname()),
                $matches
            );
            $functions = array_merge($functions, $matches[1]);
        }
        $functions = array_values(array_unique($functions));
        sort($functions);
        return $functions;
    }

    /** @param string[] $violations */
    private function formatViolations(array $violations): string
    {
        return $violations === []
            ? ''
            : "Theme/core boundary violations:\n- " . implode("\n- ", $violations);
    }
}
