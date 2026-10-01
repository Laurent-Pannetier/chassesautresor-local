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

    public function testConversionAccessIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/organisateur-functions.php');
        preg_match('/ConversionModalAjaxHandler::configure\([\s\S]*?^    \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('verifier_acces_conversion', $configuration);
    }

    public function testHuntNavigationAccessIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/sidebar.php');
        preg_match('/HuntNavigationAjaxHandler::configure\([\s\S]*?^        \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('HuntNavigationAccessService', $configuration);
        self::assertStringNotContainsString('utilisateur_est_engage_dans_chasse', $configuration);
    }

    public function testRiddleSidebarBusinessCallbacksAreNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/enigme/affichage.php');
        preg_match('/RiddleSidebarAjaxHandler::configure\([\s\S]*?^        \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('recuperer_id_chasse_associee', $configuration);
        self::assertStringNotContainsString('RiddleRenderCacheHookHandler', $configuration);
        self::assertStringNotContainsString('wp_cache_delete', $configuration);
    }

    public function testAdminConversionServiceIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/admin-functions.php');
        preg_match('/AdminAjaxHandler::configure\([\s\S]*?^\);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('cat_get_conversion_service', $configuration);
    }

    public function testAdminHuntCacheInvalidationIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/admin-functions.php');
        preg_match('/AdminAjaxHandler::configure\([\s\S]*?^\);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('chasse_clear_infos_affichage_cache', $configuration);
    }

    public function testHomepageHuntFilterIsNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/homepage-filters.php');
        preg_match('/HuntFilterAjaxHandler::configure\([\s\S]*?^\);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('ca_home_filter_chasse_ids', $configuration);
    }

    public function testAccountImportantMessagesAreNotInjectedByTheme(): void
    {
        $source = (string) file_get_contents(self::THEME_PATH . '/inc/user-functions.php');
        preg_match('/AccountSectionAjaxHandler::configure\([\s\S]*?^    \);/m', $source, $matches);
        $configuration = $matches[0] ?? null;

        self::assertIsString($configuration);
        self::assertStringNotContainsString('myaccount_get_important_messages', $configuration);
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
